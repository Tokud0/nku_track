<?php

namespace app\controllers;

use Yii;
use app\models\Project;
use app\models\ProjectSearch;
use app\models\ProjectSpec;
use app\models\ProjectDocument;
use app\models\Task;
use app\models\Roadmap;
use app\models\RoadmapStage;
use app\models\RoadmapStageGoal;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use app\models\User;
use yii\web\Response;

/**
 * ProjectController implements the CRUD actions for Project model.
 */
class ProjectController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                    'upload-documents' => ['POST'],
                    'delete-document' => ['POST'],
                    'finish' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all Project models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new ProjectSearch();
        $user = Yii::$app->user->identity;
        
        // Определяем фильтры в зависимости от роли
        $managerId = null;
        $executorId = null;
        
        $departmentId = null;
        
        // Ректор видит все проекты (только просмотр)
        if ($user->role === User::ROLE_RECTOR) {
            $managerId = null;
            $departmentId = null;
        } elseif ($user->role === User::ROLE_MANAGER || $user->role === User::ROLE_HEAD || $user->role === User::ROLE_TOP_MANAGER || $user->role === User::ROLE_ADMIN) {
            // Руководитель, топ-менеджер и админ видят все проекты
            if ($user->role === User::ROLE_HEAD || $user->role === User::ROLE_ADMIN) {
                $managerId = null;
            } else {
                // Менеджер и топ-менеджер видят проекты своего подразделения
                if ($user->department_id) {
                    $departmentId = $user->department_id;
                }
            }
        } elseif ($user->role === User::ROLE_EXECUTOR) {
            // Исполнитель видит только проекты своего подразделения
            // Если у исполнителя нет подразделения, он не видит проекты
            if ($user->department_id) {
                $departmentId = $user->department_id;
            } else {
                // Исполнитель без подразделения не видит проекты
                // Устанавливаем несуществующий ID, чтобы получить пустой результат
                $departmentId = new \MongoDB\BSON\ObjectId('000000000000000000000000');
            }
        }
        
        // Если в запросе указано подразделение, используем его (но не для ректора - ректор видит все)
        if ($user->role !== User::ROLE_RECTOR && 
            isset(Yii::$app->request->queryParams['ProjectSearch']['department_id']) && 
            !empty(Yii::$app->request->queryParams['ProjectSearch']['department_id'])) {
            $departmentId = Yii::$app->request->queryParams['ProjectSearch']['department_id'];
        }
        
        // Для ректора всегда показываем все проекты (departmentId = null)
        // Также очищаем параметр department_id из queryParams для ректора, чтобы фильтр из формы не применялся
        if ($user->role === User::ROLE_RECTOR) {
            $departmentId = null;
            // Удаляем фильтр по подразделению из параметров запроса для ректора
            if (isset(Yii::$app->request->queryParams['ProjectSearch']['department_id'])) {
                unset(Yii::$app->request->queryParams['ProjectSearch']['department_id']);
            }
        }
        
        // Проекты, где пользователь прикреплён к задаче (из другого подразделения или без подразделения)
        $includeProjectIds = [];
        if ($user->role !== User::ROLE_ADMIN && $user->role !== User::ROLE_RECTOR) {
            $tasksWithMe = Task::find()
                ->where([
                    '$or' => [
                        ['executor_user_ids' => $user->_id],
                        ['executor_user_from_department_id' => $user->_id],
                        ['executor_user_from_subdepartment_id' => $user->_id],
                    ],
                ])
                ->select(['project_id'])
                ->all();
            foreach ($tasksWithMe as $t) {
                if ($t->project_id) {
                    $includeProjectIds[(string)$t->project_id] = $t->project_id;
                }
            }
            $approvedReqs = \app\models\TaskExecutorRequest::find()
                ->where(['user_id' => $user->_id, 'status' => \app\models\TaskExecutorRequest::STATUS_APPROVED])
                ->all();
            foreach ($approvedReqs as $req) {
                $task = Task::findOne(['_id' => $req->task_id]);
                if ($task && $task->project_id) {
                    $pid = $task->project_id;
                    $includeProjectIds[(string)$pid] = $pid instanceof \MongoDB\BSON\ObjectId ? $pid : new \MongoDB\BSON\ObjectId($pid);
                }
            }
            $includeProjectIds = array_values($includeProjectIds);
        }
        
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, $managerId, null, $departmentId, $includeProjectIds);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Project model.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        $user = Yii::$app->user->identity;
        
        // Проверка доступа
        $this->checkAccess($model);
        
        // Загружаем ТЗ для проекта
        $spec = ProjectSpec::findOne(['project_id' => $model->_id]);
        
        // Задачи проекта (не архивные)
        $tasks = Task::find()
            ->where(['project_id' => $model->_id])
            ->andWhere(['$or' => [
                ['is_archived' => false],
                ['is_archived' => ['$exists' => false]],
            ]])
            ->all();
        // Прикреплённые из другого подразделения видят только свои задачи
        if (!$model->isGlobal() && $model->department_id && $user->role !== User::ROLE_ADMIN && $user->role !== User::ROLE_RECTOR) {
            $inProjectDepartment = $user->department_id && (string)$user->department_id === (string)$model->department_id;
            if (!$inProjectDepartment) {
                $tasks = array_filter($tasks, function ($task) use ($user) {
                    return $task->isAssignedToUser($user);
                });
            }
        }
        
        $userGlobalRole = $model->isGlobal() ? \app\models\GlobalProjectRole::getUserRole($user->_id) : null;

        $documents = ProjectDocument::find()
            ->where(['project_id' => $model->_id])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        $canManageDocuments = $this->canManageProjectDocuments($model, $user, $userGlobalRole);
        
        return $this->render('view', [
            'model' => $model,
            'spec' => $spec,
            'tasks' => array_values($tasks),
            'userGlobalRole' => $userGlobalRole,
            'documents' => $documents,
            'canManageDocuments' => $canManageDocuments,
        ]);
    }

    /**
     * Upload multiple official documents for project (PDF/DOC/DOCX).
     * Available for TOPs and department HEADs (and admin). Rector can only view.
     *
     * @param string $id Project ID
     * @return Response
     * @throws NotFoundHttpException
     */
    public function actionUploadDocuments($id)
    {
        $project = $this->findModel($id);
        $user = Yii::$app->user->identity;

        // Must have at least view-access to the project
        $this->checkAccess($project);

        $userGlobalRole = $project->isGlobal() ? \app\models\GlobalProjectRole::getUserRole($user->_id) : null;
        if (!$this->canManageProjectDocuments($project, $user, $userGlobalRole)) {
            Yii::$app->session->setFlash('error', 'У вас нет прав для загрузки документов проекта.');
            return $this->redirect(['view', 'id' => (string)$project->_id]);
        }

        $files = UploadedFile::getInstancesByName('documents');
        if (empty($files)) {
            Yii::$app->session->setFlash('error', 'Файлы не выбраны.');
            return $this->redirect(['view', 'id' => (string)$project->_id]);
        }

        $allowedMimeTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];
        $allowedExtensions = ['pdf', 'doc', 'docx'];
        $maxBytes = 15 * 1024 * 1024; // keep below Mongo 16MB doc limit

        $saved = 0;
        $errors = [];

        foreach ($files as $file) {
            if (!$file) {
                continue;
            }

            $tempPath = $file->tempName ?? '';
            if ($tempPath === '' || !is_uploaded_file($tempPath)) {
                $errors[] = 'Файл не получен: ' . $file->name . '. Увеличьте в php.ini upload_max_filesize и post_max_size (например до 16M и 20M) и перезапустите сервер.';
                continue;
            }

            $ext = strtolower((string)$file->extension);
            if (!in_array($ext, $allowedExtensions, true)) {
                $errors[] = 'Недопустимое расширение: ' . $file->name . ' (разрешены PDF/DOC/DOCX).';
                continue;
            }

            // Для больших файлов браузер/прокси часто шлёт пустой MIME или application/octet-stream — проверяем по содержимому
            $mime = $file->type;
            if (empty($mime) || $mime === 'application/octet-stream') {
                if (function_exists('finfo_open') && $tempPath !== '' && is_file($tempPath)) {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    if ($finfo) {
                        $detected = finfo_file($finfo, $tempPath);
                        finfo_close($finfo);
                        if (!empty($detected) && in_array($detected, $allowedMimeTypes, true)) {
                            $mime = $detected;
                        }
                    }
                }
                // Если MIME так и не определили — разрешаем по расширению для наших форматов (типично для больших загрузок)
                if (empty($mime) || $mime === 'application/octet-stream') {
                    $mime = [
                        'pdf' => 'application/pdf',
                        'doc' => 'application/msword',
                        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    ][$ext] ?? $mime;
                }
            }
            if (empty($mime) || !in_array($mime, $allowedMimeTypes, true)) {
                $errors[] = 'Недопустимый тип файла: ' . $file->name . ' (разрешены PDF/DOC/DOCX). Получен тип: ' . ($mime ?: 'не определён') . '.';
                continue;
            }

            if ($file->size > $maxBytes) {
                $errors[] = 'Слишком большой файл: ' . $file->name . ' (макс. 15 МБ).';
                continue;
            }

            $data = @file_get_contents($tempPath);
            if ($data === false) {
                $errors[] = 'Не удалось прочитать файл: ' . $file->name . '.';
                continue;
            }

            $doc = new ProjectDocument();
            $doc->project_id = $project->_id instanceof \MongoDB\BSON\ObjectId ? $project->_id : new \MongoDB\BSON\ObjectId((string)$project->_id);
            $doc->uploaded_by_user_id = $user->_id;
            $doc->file_name = $file->name;
            $doc->file_type = $mime;
            $doc->file_size = (int)$file->size;
            $doc->file_data = new \MongoDB\BSON\Binary($data, \MongoDB\BSON\Binary::TYPE_GENERIC);

            if ($doc->save()) {
                $saved++;
            } else {
                $firstErrors = $doc->getFirstErrors();
                $errors[] = 'Ошибка сохранения файла ' . $file->name . ': ' . (!empty($firstErrors) ? implode(', ', $firstErrors) : 'неизвестная ошибка');
            }
        }

        if ($saved > 0) {
            Yii::$app->session->setFlash('success', 'Загружено документов: ' . $saved . '.');
        }
        if (!empty($errors)) {
            Yii::$app->session->setFlash('error', implode("\n", $errors));
        }

        return $this->redirect(['view', 'id' => (string)$project->_id]);
    }

    /**
     * Download uploaded project document.
     *
     * @param string $id Document ID
     * @return Response
     * @throws NotFoundHttpException
     */
    public function actionDownloadDocument($id)
    {
        $doc = ProjectDocument::findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
        if (!$doc) {
            throw new NotFoundHttpException('Документ не найден.');
        }

        $project = Project::findOne(['_id' => $doc->project_id]);
        if (!$project) {
            throw new NotFoundHttpException('Проект не найден.');
        }

        // View access: all involved users (via project access rules) and rector
        $this->checkAccess($project);

        if (empty($doc->file_data)) {
            throw new NotFoundHttpException('Файл документа не найден.');
        }

        $data = $doc->file_data instanceof \MongoDB\BSON\Binary ? $doc->file_data->getData() : $doc->file_data;

        return Yii::$app->response->sendContentAsFile(
            $data,
            $doc->file_name ?: 'document',
            ['mimeType' => $doc->file_type ?: 'application/octet-stream']
        );
    }

    /**
     * Delete uploaded project document (allows re-upload).
     *
     * @param string $id Document ID
     * @return Response
     * @throws NotFoundHttpException
     */
    public function actionDeleteDocument($id)
    {
        $doc = ProjectDocument::findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
        if (!$doc) {
            throw new NotFoundHttpException('Документ не найден.');
        }

        $project = Project::findOne(['_id' => $doc->project_id]);
        if (!$project) {
            throw new NotFoundHttpException('Проект не найден.');
        }

        // Must have view access to the project
        $this->checkAccess($project);

        $user = Yii::$app->user->identity;
        $userGlobalRole = $project->isGlobal() ? \app\models\GlobalProjectRole::getUserRole($user->_id) : null;
        if (!$this->canManageProjectDocuments($project, $user, $userGlobalRole)) {
            Yii::$app->session->setFlash('error', 'У вас нет прав для удаления документов проекта.');
            return $this->redirect(['view', 'id' => (string)$project->_id]);
        }

        $doc->delete();
        Yii::$app->session->setFlash('success', 'Документ удалён.');

        return $this->redirect(['view', 'id' => (string)$project->_id]);
    }

    /**
     * Creates a new Project model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $user = Yii::$app->user->identity;
        
        // Руководитель, топ-менеджер и админ могут создавать проект
        if (!in_array($user->role, [User::ROLE_HEAD, User::ROLE_TOP_MANAGER, User::ROLE_ADMIN])) {
            Yii::$app->session->setFlash('error', 'У вас нет прав для создания проектов.');
            return $this->redirect(['index']);
        }
        
        $model = new Project();
        $model->manager_id = $user->_id;
        $model->status = Project::STATUS_DRAFT;
        $model->progress = 0;

        if ($model->load(Yii::$app->request->post())) {
            // Конвертируем даты из строк в UTCDateTime
            if (!empty($_POST['Project']['start_date'])) {
                $model->start_date = new \MongoDB\BSON\UTCDateTime(strtotime($_POST['Project']['start_date']) * 1000);
            }
            if (!empty($_POST['Project']['end_date'])) {
                $model->end_date = new \MongoDB\BSON\UTCDateTime(strtotime($_POST['Project']['end_date']) * 1000);
            }
            // Конвертируем department_id в ObjectId если это строка
            if (!empty($_POST['Project']['department_id'])) {
                $model->department_id = new \MongoDB\BSON\ObjectId($_POST['Project']['department_id']);
            } elseif (in_array($user->role, [User::ROLE_HEAD, User::ROLE_TOP_MANAGER]) && $model->isNewRecord) {
                // Для руководителя и топ-менеджера при создании используем его подразделение
                if ($user->department_id) {
                    $model->department_id = $user->department_id;
                }
            }
            
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Проект успешно создан.');
                return $this->redirect(['view', 'id' => (string)$model->_id]);
            }
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Project model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $user = Yii::$app->user->identity;
        
        // Глобальный проект: админ, глоб. руководитель, глоб. топ-менеджер
        if ($model->isGlobal()) {
            $userGlobalRole = \app\models\GlobalProjectRole::getUserRole($user->_id);
            $canEditGlobal = $user->role === User::ROLE_ADMIN || 
                $userGlobalRole === \app\models\GlobalProjectRole::ROLE_RECTOR || 
                $userGlobalRole === \app\models\GlobalProjectRole::ROLE_GLOBAL_TOP_MANAGER;
            if (!$canEditGlobal) {
                Yii::$app->session->setFlash('error', 'У вас нет прав для редактирования глобальных проектов.');
                return $this->redirect(['view', 'id' => $id]);
            }
        }
        // Обычный проект
        elseif ($user->role === User::ROLE_RECTOR) {
            Yii::$app->session->setFlash('error', 'Ректор может только просматривать проекты.');
            return $this->redirect(['view', 'id' => $id]);
        }
        elseif ($user->role === User::ROLE_ADMIN) {
            // Разрешаем редактирование
        }
        elseif ($user->role === User::ROLE_HEAD && 
                $model->department_id && $user->department_id &&
                (string)$model->department_id === (string)$user->department_id) {
            // Разрешаем редактирование
        }
        elseif ($user->role === User::ROLE_TOP_MANAGER && 
                $model->department_id && $user->department_id &&
                (string)$model->department_id === (string)$user->department_id) {
            // Разрешаем редактирование
        }
        else {
            Yii::$app->session->setFlash('error', 'Вы не можете редактировать этот проект.');
            return $this->redirect(['view', 'id' => $id]);
        }

        if ($model->load(Yii::$app->request->post())) {
            // Конвертируем даты из строк в UTCDateTime
            if (!empty($_POST['Project']['start_date'])) {
                $model->start_date = new \MongoDB\BSON\UTCDateTime(strtotime($_POST['Project']['start_date']) * 1000);
            }
            if (!empty($_POST['Project']['end_date'])) {
                $model->end_date = new \MongoDB\BSON\UTCDateTime(strtotime($_POST['Project']['end_date']) * 1000);
            }
            // Конвертируем department_id в ObjectId если это строка
            if (!empty($_POST['Project']['department_id'])) {
                $model->department_id = new \MongoDB\BSON\ObjectId($_POST['Project']['department_id']);
            }
            // При редактировании менеджер не может менять подразделение - остается текущее
            
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Проект успешно обновлен.');
                return $this->redirect(['view', 'id' => (string)$model->_id]);
            }
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Project model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        $user = Yii::$app->user->identity;
        
        // Админ может удалять все проекты
        // Ректор может удалять проекты своего подразделения
        $canDelete = false;
        if ($user->role === User::ROLE_ADMIN) {
            $canDelete = true;
        } elseif ($user->role === User::ROLE_RECTOR && 
                  $model->department_id && $user->department_id &&
                  (string)$model->department_id === (string)$user->department_id) {
            $canDelete = true;
        }
        
        if (!$canDelete) {
            Yii::$app->session->setFlash('error', 'Вы не можете удалить этот проект.');
            return $this->redirect(['view', 'id' => $id]);
        }
        
        // Удаляем все задачи, связанные с проектом
        Task::deleteAll(['project_id' => $model->_id]);
        
        $model->delete();
        Yii::$app->session->setFlash('success', 'Проект успешно удален.');

        return $this->redirect(['index']);
    }


    /**
     * Displays kanban board for project tasks
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionKanban($id)
    {
        $model = $this->findModel($id);
        $user = Yii::$app->user->identity;
        
        // Проверка доступа к проекту
        $this->checkAccess($model);
        
        // Задачи для канбана; прикреплённые из другого подразделения видят только свои
        $tasksQuery = Task::find()
            ->where(['project_id' => $model->_id])
            ->andWhere(['$or' => [
                ['is_archived' => false],
                ['is_archived' => ['$exists' => false]],
            ]])
            ->orderBy(['created_at' => SORT_ASC]);
        $tasks = $tasksQuery->all();
        if (!$model->isGlobal() && $model->department_id && $user->role !== User::ROLE_ADMIN && $user->role !== User::ROLE_RECTOR) {
            $inProjectDepartment = $user->department_id && (string)$user->department_id === (string)$model->department_id;
            if (!$inProjectDepartment) {
                $tasks = array_values(array_filter($tasks, function ($task) use ($user) {
                    return $task->isAssignedToUser($user);
                }));
            }
        }
        
        return $this->render('kanban', [
            'model' => $model,
            'tasks' => $tasks,
        ]);
    }

    /**
     * Список задач проекта (отдельной страницей)
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException
     */
    public function actionTasksList($id)
    {
        $model = $this->findModel($id);
        $user = Yii::$app->user->identity;

        // Проверка доступа к проекту
        $this->checkAccess($model);

        // Задачи (не архивные); прикреплённые из другого подразделения видят только свои
        $tasksQuery = Task::find()
            ->where(['project_id' => $model->_id])
            ->andWhere(['$or' => [
                ['is_archived' => false],
                ['is_archived' => ['$exists' => false]],
            ]])
            ->orderBy(['created_at' => SORT_ASC]);
        $tasks = $tasksQuery->all();
        if (!$model->isGlobal() && $model->department_id && $user->role !== User::ROLE_ADMIN && $user->role !== User::ROLE_RECTOR) {
            $inProjectDepartment = $user->department_id && (string)$user->department_id === (string)$model->department_id;
            if (!$inProjectDepartment) {
                $tasks = array_values(array_filter($tasks, function ($task) use ($user) {
                    return $task->isAssignedToUser($user);
                }));
            }
        }

        return $this->render('tasks-list', [
            'model' => $model,
            'tasks' => $tasks,
        ]);
    }

    /**
     * Показывает архив задач проекта
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionArchive($id)
    {
        $model = $this->findModel($id);
        $user = Yii::$app->user->identity;
        
        // Проверка доступа к проекту
        $this->checkAccess($model);
        
        // Получаем все архивные задачи проекта
        $tasksQuery = Task::find()
            ->where(['project_id' => $model->_id, 'is_archived' => true])
            ->orderBy(['created_at' => SORT_DESC]);
        
        // Фильтруем для исполнителя или прикреплённого из другого подразделения
        $filterByAssigned = false;
        if ($user->role === User::ROLE_EXECUTOR) {
            $filterByAssigned = true;
        } elseif (!$model->isGlobal() && $model->department_id && $user->role !== User::ROLE_ADMIN && $user->role !== User::ROLE_RECTOR) {
            $inProjectDepartment = $user->department_id && (string)$user->department_id === (string)$model->department_id;
            if (!$inProjectDepartment) {
                $filterByAssigned = true;
            }
        }
        if ($filterByAssigned) {
            $allTasks = Task::find()
                ->where(['project_id' => $model->_id, 'is_archived' => true])
                ->all();
            $visibleTaskIds = [];
            foreach ($allTasks as $task) {
                if ($task->isAssignedToUser($user)) {
                    $visibleTaskIds[] = $task->_id;
                }
            }
            if (!empty($visibleTaskIds)) {
                $tasksQuery->andWhere(['_id' => ['$in' => $visibleTaskIds]]);
            } else {
                $tasksQuery->andWhere(['_id' => ['$in' => []]]);
            }
        }
        
        $tasks = $tasksQuery->all();
        
        return $this->render('archive', [
            'model' => $model,
            'tasks' => $tasks,
        ]);
    }

    /**
     * Завершение проекта (перевод в STATUS_FINISHED).
     * Только HEAD/TOP_MANAGER своего подразделения. Все задачи проекта должны
     * быть либо в статусе done, либо в архиве.
     *
     * @param string $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionFinish($id)
    {
        $model = $this->findModel($id);
        $user = Yii::$app->user->identity;

        if ($model->isGlobal()) {
            Yii::$app->session->setFlash('error', 'Глобальные проекты завершаются в другом разделе.');
            return $this->redirect(['view', 'id' => (string)$model->_id]);
        }

        $sameDepartment = $model->department_id && $user->department_id &&
            (string)$model->department_id === (string)$user->department_id;
        $isAdmin = $user->role === User::ROLE_ADMIN;
        $isDeptHeadOrTop = in_array($user->role, [User::ROLE_HEAD, User::ROLE_TOP_MANAGER]) && $sameDepartment;
        if (!$isAdmin && !$isDeptHeadOrTop) {
            Yii::$app->session->setFlash('error', 'У вас нет прав для завершения этого проекта.');
            return $this->redirect(['view', 'id' => (string)$model->_id]);
        }

        if ($model->status === Project::STATUS_FINISHED) {
            return $this->redirect(['view', 'id' => (string)$model->_id]);
        }

        if (!self::canFinishProject($model)) {
            Yii::$app->session->setFlash('error', 'Нельзя завершить проект: не все задачи выполнены или находятся в архиве.');
            return $this->redirect(['view', 'id' => (string)$model->_id]);
        }

        $model->status = Project::STATUS_FINISHED;
        if ($model->save(false)) {
            Yii::$app->session->setFlash('success', 'Проект успешно завершён.');
        } else {
            Yii::$app->session->setFlash('error', 'Ошибка при сохранении проекта.');
        }
        return $this->redirect(['view', 'id' => (string)$model->_id]);
    }

    /**
     * Проверяет, можно ли завершить проект: должна быть хотя бы одна задача,
     * и каждая из них либо STATUS_DONE, либо is_archived === true.
     */
    public static function canFinishProject(Project $model): bool
    {
        $tasks = Task::find()->where(['project_id' => $model->_id])->all();
        if (empty($tasks)) {
            return false;
        }
        foreach ($tasks as $task) {
            $isDone = $task->status === Task::STATUS_DONE;
            $isArchived = $task->is_archived === true;
            if (!$isDone && !$isArchived) {
                return false;
            }
        }
        return true;
    }

    /**
     * Displays mind map for project
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionMindMap($id)
    {
        $model = $this->findModel($id);
        
        // Проверка доступа
        $this->checkAccess($model);
        
        // Этапы из ТЗ (ProjectSpec milestones)
        $spec = ProjectSpec::findOne(['project_id' => $model->_id]);
        $milestones = is_array($spec->milestones ?? null) ? $spec->milestones : [];
        
        // Все задачи проекта (не архивные)
        $tasks = Task::find()
            ->where([
                'project_id' => $model->_id,
                '$or' => [
                    ['is_archived' => false],
                    ['is_archived' => ['$exists' => false]],
                ]
            ])
            ->orderBy(['created_at' => SORT_ASC])
            ->all();
        
        // Группируем задачи по этапу: этап (с метрикой) -> задачи (подзадачи не показываем)
        $stagesData = [];
        foreach ($milestones as $idx => $m) {
            $stageName = $m['name'] ?? 'Этап ' . ($idx + 1);
            $deadline = isset($m['deadline']) && $m['deadline'] ? ' (до ' . $m['deadline'] . ')' : '';
            $stageTasks = [];
            foreach ($tasks as $task) {
                $mi = $task->milestone_index;
                if ($mi !== null && $mi !== '' && (int)$mi === (int)$idx) {
                    $stageTasks[] = [
                        'id' => (string)$task->_id,
                        'title' => $task->title,
                        'description' => $task->description ?? '',
                    ];
                }
            }
            $stagesData[] = [
                'id' => 'stage_' . $idx,
                'index' => $idx,
                'name' => $stageName . $deadline,
                'tasks' => $stageTasks,
            ];
        }
        // Задачи без этапа
        $noStageTasks = [];
        foreach ($tasks as $task) {
            if ($task->milestone_index === null || $task->milestone_index === '') {
                $noStageTasks[] = [
                    'id' => (string)$task->_id,
                    'title' => $task->title,
                    'description' => $task->description ?? '',
                ];
            }
        }
        if (!empty($noStageTasks)) {
            $stagesData[] = [
                'id' => 'stage_no',
                'index' => -1,
                'name' => 'Без этапа',
                'tasks' => $noStageTasks,
            ];
        }
        
        return $this->render('mind-map', [
            'model' => $model,
            'stagesData' => $stagesData,
        ]);
    }

    /**
     * Finds the Project model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return Project the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Project::findOne(['_id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('Запрашиваемая страница не найдена.');
    }

    /**
     * Проверка доступа к проекту
     * @param Project $model
     * @throws NotFoundHttpException
     */
    protected function checkAccess($model)
    {
        $user = Yii::$app->user->identity;
        
        // Админ имеет полный доступ
        if ($user->role === User::ROLE_ADMIN) {
            return;
        }
        
        // Проверяем, является ли проект глобальным
        if ($model->isGlobal()) {
            // В глобальных проектах все пользователи имеют доступ по умолчанию
            return;
        }
        
        // Ректор имеет доступ ко всем проектам (только просмотр)
        if ($user->role === User::ROLE_RECTOR) {
            return;
        }
        
        // Руководитель имеет доступ к проектам своего подразделения
        if ($user->role === User::ROLE_HEAD) {
            if ($model->department_id && $user->department_id && 
                (string)$model->department_id === (string)$user->department_id) {
                return;
            }
        }
        
        // Топ-менеджер имеет доступ к проектам своего подразделения
        if ($user->role === User::ROLE_TOP_MANAGER) {
            if ($model->department_id && $user->department_id && 
                (string)$model->department_id === (string)$user->department_id) {
                return;
            }
        }
        
        // Менеджер имеет доступ к проектам своего подразделения
        if ($user->role === User::ROLE_MANAGER) {
            if ($model->department_id && $user->department_id && 
                (string)$model->department_id === (string)$user->department_id) {
                return;
            }
        }
        
        // Исполнитель имеет доступ только если он в подразделении проекта
        if ($user->role === User::ROLE_EXECUTOR) {
            if ($model->department_id && $user->department_id && 
                (string)$model->department_id === (string)$user->department_id) {
                return;
            }
        }
        
        // Прикреплённые к задаче (из другого подразделения или без подразделения) видят проект
        $projectTasks = Task::find()->where(['project_id' => $model->_id])->all();
        foreach ($projectTasks as $task) {
            if ($task->isAssignedToUser($user)) {
                return;
            }
        }
        
        throw new NotFoundHttpException('У вас нет доступа к этому проекту.');
    }

    /**
     * Who can upload/delete project official documents.
     *
     * - Department projects: ADMIN or (HEAD/TOP_MANAGER of the same department)
     * - Global projects: ADMIN or GLOBAL_TOP_MANAGER
     * - Rector: view only
     */
    protected function canManageProjectDocuments(Project $project, User $user, $userGlobalRole = null): bool
    {
        if ($user->role === User::ROLE_ADMIN) {
            return true;
        }

        if ($user->role === User::ROLE_RECTOR) {
            return false;
        }

        if ($project->isGlobal()) {
            return $userGlobalRole === \app\models\GlobalProjectRole::ROLE_GLOBAL_TOP_MANAGER;
        }

        if (in_array($user->role, [User::ROLE_HEAD, User::ROLE_TOP_MANAGER], true)) {
            return $project->department_id && $user->department_id
                && (string)$project->department_id === (string)$user->department_id;
        }

        return false;
    }
}

