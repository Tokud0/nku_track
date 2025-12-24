<?php

namespace app\controllers;

use Yii;
use app\models\Project;
use app\models\ProjectSearch;
use app\models\ProjectSpec;
use app\models\Task;
use app\models\Roadmap;
use app\models\RoadmapStage;
use app\models\RoadmapStageGoal;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use app\models\User;

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
        
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, $managerId, null, $departmentId);

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
        
        // Проверка доступа
        $this->checkAccess($model);
        
        // Загружаем ТЗ для проекта
        $spec = ProjectSpec::findOne(['project_id' => $model->_id]);
        
        return $this->render('view', [
            'model' => $model,
            'spec' => $spec,
        ]);
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
        
        // Ректор может только просматривать, не может редактировать
        if ($user->role === User::ROLE_RECTOR) {
            Yii::$app->session->setFlash('error', 'Ректор может только просматривать проекты.');
            return $this->redirect(['view', 'id' => $id]);
        }
        
        // Админ может редактировать все проекты
        if ($user->role === User::ROLE_ADMIN) {
            // Разрешаем редактирование
        }
        // Руководитель может редактировать все проекты своего подразделения
        elseif ($user->role === User::ROLE_HEAD && 
                $model->department_id && $user->department_id &&
                (string)$model->department_id === (string)$user->department_id) {
            // Разрешаем редактирование
        }
        // Топ-менеджер может редактировать проекты своего подразделения
        elseif ($user->role === User::ROLE_TOP_MANAGER && 
                $model->department_id && $user->department_id &&
                (string)$model->department_id === (string)$user->department_id) {
            // Разрешаем редактирование
        }
        // Менеджер не может редактировать проекты, только задачи
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
        
        return $this->render('kanban', [
            'model' => $model,
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
        
        // Фильтруем задачи по видимости для исполнителя
        if ($user->role === User::ROLE_EXECUTOR) {
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
                $tasksQuery->andWhere(['_id' => ['$in' => []]]); // Пустой результат
            }
        }
        
        $tasks = $tasksQuery->all();
        
        return $this->render('archive', [
            'model' => $model,
            'tasks' => $tasks,
        ]);
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
        
        // Получаем задачи проекта (не архивные) с их подзадачами
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
        
        // Формируем данные для mind map: задача -> её подзадачи
        $tasksData = [];
        foreach ($tasks as $task) {
            $subtasks = [];
            if (is_array($task->subtasks)) {
                foreach ($task->subtasks as $subtask) {
                    // Показываем все подзадачи, включая выполненные
                    $subtasks[] = [
                        'text' => trim($subtask['text'] ?? ''),
                        'completed' => isset($subtask['completed']) && $subtask['completed'] === true,
                    ];
                }
            }
            
            $tasksData[] = [
                'id' => (string)$task->_id,
                'title' => $task->title,
                'description' => $task->description ?? '',
                'subtasks' => $subtasks,
            ];
        }
        
        return $this->render('mind-map', [
            'model' => $model,
            'tasks' => $tasksData,
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
        
        throw new NotFoundHttpException('У вас нет доступа к этому проекту.');
    }
}

