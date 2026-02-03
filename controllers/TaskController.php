<?php

namespace app\controllers;

use Yii;
use app\models\Task;
use app\models\Project;
use app\models\ProjectSpec;
use app\models\User;
use app\models\GlobalProjectRole;
use app\models\TaskExecutorRequest;
use app\models\Department;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;

/**
 * TaskController implements the CRUD actions for Task model.
 */
class TaskController extends Controller
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
                    'change-status' => ['POST'],
                    'toggle-subtask' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Creates a new Task model.
     * If creation is successful, the browser will be redirected to the project view page.
     * @param string $project_id
     * @return mixed
     */
    public function actionCreate($project_id)
    {
        $project = Project::findOne(['_id' => $project_id]);
        if (!$project) {
            throw new NotFoundHttpException('Проект не найден.');
        }

        $user = Yii::$app->user->identity;
        
        // Проверяем, является ли проект глобальным
        $isGlobalProject = $project->isGlobal();
        
        // Для глобального проекта: админ, глоб. руководитель, глоб. топ-менеджер, глоб. менеджер
        // Глоб. исполнитель НЕ может создавать задачи
        if ($isGlobalProject) {
            $userGlobalRole = GlobalProjectRole::getUserRole($user->_id);
            $canCreateGlobal = $user->role === User::ROLE_ADMIN || 
                in_array($userGlobalRole, [
                    GlobalProjectRole::ROLE_RECTOR,
                    GlobalProjectRole::ROLE_GLOBAL_TOP_MANAGER,
                    GlobalProjectRole::ROLE_GLOBAL_MANAGER,
                ]);
            if (!$canCreateGlobal) {
                Yii::$app->session->setFlash('error', 'У вас нет прав для создания задач в глобальном проекте.');
                return $this->redirect($project->direction_id ? ['/direction/view', 'id' => (string)$project->direction_id] : ['global-project/view', 'id' => $project_id]);
            }
        } else {
            // Для обычных проектов: руководитель, топ-менеджер, менеджер и админ могут создавать задачи
            if (!in_array($user->role, [User::ROLE_HEAD, User::ROLE_TOP_MANAGER, User::ROLE_MANAGER, User::ROLE_ADMIN])) {
                Yii::$app->session->setFlash('error', 'У вас нет прав для создания задач.');
                return $this->redirect(['project/view', 'id' => $project_id]);
            }
            
            // Проверяем доступ к проекту (по подразделению)
            if ($user->role !== User::ROLE_ADMIN) {
                if (!$project->department_id || !$user->department_id || 
                    (string)$project->department_id !== (string)$user->department_id) {
                    Yii::$app->session->setFlash('error', 'Вы не можете создавать задачи для проектов других подразделений.');
                    return $this->redirect(['project/view', 'id' => $project_id]);
                }
            }
        }

        $model = new Task();
        $projectIdObj = is_string($project_id) ? new \MongoDB\BSON\ObjectId($project_id) : $project_id;
        
        // Устанавливаем значения по умолчанию
        $model->project_id = $projectIdObj;
        $model->creator_id = $user->_id;
        $model->status = Task::STATUS_TODO;
        $model->priority = Task::PRIORITY_MEDIUM;
        $model->progress = 0;
        $model->attachments = [];

        // Этапы из ТЗ проекта для выбора при создании задачи
        $spec = ProjectSpec::findOne(['project_id' => $projectIdObj]);
        $milestones = (is_array($spec->milestones ?? null)) ? $spec->milestones : [];

        // Обрабатываем executor_user_ids до load() (глобальный проект и «поиск любого» в обычном)
        $executorUserIdsData = null;
        if (!empty($_POST['Task']['executor_user_ids'])) {
            $executorIdsJson = $_POST['Task']['executor_user_ids'];
            $executorIds = json_decode($executorIdsJson, true);
            if (is_array($executorIds) && !empty($executorIds)) {
                $executorUserIdsData = $executorIds;
            }
            unset($_POST['Task']['executor_user_ids']);
        }
        
        // Обрабатываем responsible_user_ids до load(), чтобы избежать ошибки "Array to string conversion"
        $responsibleUserIdsData = null;
        if ($isGlobalProject && !empty($_POST['Task']['responsible_user_ids'])) {
            $responsibleIdsJson = $_POST['Task']['responsible_user_ids'];
            $responsibleIds = json_decode($responsibleIdsJson, true);
            if (is_array($responsibleIds) && !empty($responsibleIds)) {
                $responsibleUserIdsData = $responsibleIds;
            }
            // Временно удаляем из POST, чтобы load() не пытался его загрузить
            unset($_POST['Task']['responsible_user_ids']);
        }
        
        if ($model->load(Yii::$app->request->post())) {
            // ВАЖНО: После load() нужно снова установить project_id и creator_id,
            // так как они могут быть перезаписаны из POST данных
            $model->project_id = $projectIdObj;
            $model->creator_id = $user->_id;
            
            // Обрабатываем подзадачи
            if (isset($_POST['subtasks']) && is_array($_POST['subtasks'])) {
                $subtasks = [];
                foreach ($_POST['subtasks'] as $subtaskText) {
                    $subtaskText = trim($subtaskText);
                    if (!empty($subtaskText)) {
                        $subtasks[] = [
                            'text' => $subtaskText,
                            'completed' => false
                        ];
                    }
                }
                $model->subtasks = $subtasks;
            } else {
                $model->subtasks = [];
            }
            
            // Конвертируем даты из строк в UTCDateTime
            if (!empty($_POST['Task']['start_date'])) {
                $model->start_date = new \MongoDB\BSON\UTCDateTime(strtotime($_POST['Task']['start_date']) * 1000);
            } else {
                $model->start_date = null;
            }
            if (!empty($_POST['Task']['due_date'])) {
                $model->due_date = new \MongoDB\BSON\UTCDateTime(strtotime($_POST['Task']['due_date']) * 1000);
            } else {
                $model->due_date = null;
            }
            // Этап из ТЗ
            if (isset($_POST['Task']['milestone_index']) && $_POST['Task']['milestone_index'] !== '') {
                $model->milestone_index = (int)$_POST['Task']['milestone_index'];
            } else {
                $model->milestone_index = null;
            }
            // Обрабатываем исполнителей в зависимости от типа проекта
            if ($isGlobalProject) {
                // Для глобальных проектов: множественные исполнители
                $model->executor_user_ids = $executorUserIdsData;
                // Очищаем поля для обычных проектов
                $model->executor_user_from_department_id = null;
                $model->executor_subdepartment_id = null;
                $model->executor_user_from_subdepartment_id = null;
                
                // Обрабатываем ответственного (глобальный менеджер)
                if (!empty($_POST['Task']['responsible_user_id'])) {
                    $model->responsible_user_id = new \MongoDB\BSON\ObjectId($_POST['Task']['responsible_user_id']);
                } else {
                    $model->responsible_user_id = null;
                }
            } else {
                // Для обычных проектов: один исполнитель ИЛИ «поиск любого» (executor_user_ids)
                $model->responsible_user_id = null;
                $projectDepartmentId = $project->department_id ? (string)$project->department_id : null;

                if (!empty($executorUserIdsData)) {
                    // Режим «поиск любого сотрудника»: без подразделения — привязать; из другого подразделения — заявка
                    $directExecutorIds = [];
                    $requestUserIds = []; // пользователи, по которым создаём заявку (другое подразделение)
                    foreach ($executorUserIdsData as $uid) {
                        $uidStr = is_string($uid) ? $uid : (string)$uid;
                        $executorUser = User::findOne(['_id' => $uidStr]);
                        if (!$executorUser) continue;
                        if (!$executorUser->department_id) {
                            $directExecutorIds[] = $uidStr;
                        } elseif ($projectDepartmentId && (string)$executorUser->department_id === $projectDepartmentId) {
                            $directExecutorIds[] = $uidStr;
                        } else {
                            $requestUserIds[] = $uidStr;
                        }
                    }
                    $model->executor_user_ids = $directExecutorIds;
                    $model->executor_user_from_department_id = null;
                    $model->executor_subdepartment_id = null;
                    $model->executor_user_from_subdepartment_id = null;
                    // Заявки создаём после сохранения задачи
                } else {
                    $model->executor_user_ids = null;
                    if (!empty($_POST['Task']['executor_user_from_department_id'])) {
                        $model->executor_user_from_department_id = new \MongoDB\BSON\ObjectId($_POST['Task']['executor_user_from_department_id']);
                        $model->executor_subdepartment_id = null;
                        $model->executor_user_from_subdepartment_id = null;
                    } elseif (!empty($_POST['Task']['executor_subdepartment_id'])) {
                        $model->executor_subdepartment_id = new \MongoDB\BSON\ObjectId($_POST['Task']['executor_subdepartment_id']);
                        $model->executor_user_from_department_id = null;
                        $model->executor_user_from_subdepartment_id = null;
                    } elseif (!empty($_POST['Task']['executor_user_from_subdepartment_id'])) {
                        $model->executor_user_from_subdepartment_id = new \MongoDB\BSON\ObjectId($_POST['Task']['executor_user_from_subdepartment_id']);
                        $model->executor_user_from_department_id = null;
                        $model->executor_subdepartment_id = null;
                    } else {
                        $model->executor_user_from_department_id = null;
                        $model->executor_subdepartment_id = null;
                        $model->executor_user_from_subdepartment_id = null;
                    }
                }
            }

            // Валидируем модель
            if ($model->validate()) {
                if ($model->save(false)) {
                    // Создаём заявки на прикрепление (исполнитель из другого подразделения)
                    if (!$isGlobalProject && !empty($executorUserIdsData) && $project->department_id) {
                        $projectDepartmentId = (string)$project->department_id;
                        foreach ($executorUserIdsData as $uid) {
                            $uidStr = is_string($uid) ? $uid : (string)$uid;
                            $executorUser = User::findOne(['_id' => $uidStr]);
                            if (!$executorUser || !$executorUser->department_id) continue;
                            if ((string)$executorUser->department_id === $projectDepartmentId) continue;
                            $req = new TaskExecutorRequest();
                            $req->task_id = $model->_id;
                            $req->user_id = $executorUser->_id;
                            $req->status = TaskExecutorRequest::STATUS_PENDING;
                            $req->requested_by = $user->_id;
                            $req->save(false);
                        }
                    }
                    Yii::$app->session->setFlash('success', 'Задача успешно создана.');
                    // Редирект зависит от типа проекта - переходим на вкладку "Задачи"
                    if ($isGlobalProject) {
                        return $this->redirect(['global-project/view', 'id' => $project_id, '#' => 'tasks']);
                    } else {
                        return $this->redirect(['project/view', 'id' => $project_id, '#' => 'tasks']);
                    }
                } else {
                    Yii::$app->session->setFlash('error', 'Ошибка при сохранении задачи.');
                }
            } else {
                // Выводим ошибки валидации
                $errors = $model->getErrors();
                $errorMessages = [];
                foreach ($errors as $attribute => $messages) {
                    $errorMessages[] = $model->getAttributeLabel($attribute) . ': ' . implode(', ', $messages);
                }
                Yii::$app->session->setFlash('error', 'Ошибки валидации: ' . implode('; ', $errorMessages));
            }
        }

        return $this->render('create', [
            'model' => $model,
            'project' => $project,
            'isGlobalProject' => $isGlobalProject,
            'milestones' => $milestones,
        ]);
    }

    /**
     * Updates an existing Task model.
     * If update is successful, the browser will be redirected to the project view page.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $user = Yii::$app->user->identity;
        
        // Проверка прав доступа
        $this->checkAccess($model, $user);

        // Проверяем, является ли проект глобальным
        $isGlobalProject = $model->project && $model->project->isGlobal();
        
        // Этапы из ТЗ проекта для выбора при редактировании
        $spec = $model->project_id ? ProjectSpec::findOne(['project_id' => $model->project_id]) : null;
        $milestones = ($spec && is_array($spec->milestones ?? null)) ? $spec->milestones : [];

        // Обрабатываем executor_user_ids до load() (глобальный и «поиск любого» при редактировании)
        $executorUserIdsData = null;
        if (!empty($_POST['Task']['executor_user_ids'])) {
            $executorIdsJson = $_POST['Task']['executor_user_ids'];
            $executorIds = json_decode($executorIdsJson, true);
            if (is_array($executorIds) && !empty($executorIds)) {
                $executorUserIdsData = $executorIds;
            }
            unset($_POST['Task']['executor_user_ids']);
        }
        
        // Обрабатываем responsible_user_ids до load(), чтобы избежать ошибки "Array to string conversion"
        $responsibleUserIdsData = null;
        if ($isGlobalProject && !empty($_POST['Task']['responsible_user_ids'])) {
            $responsibleIdsJson = $_POST['Task']['responsible_user_ids'];
            $responsibleIds = json_decode($responsibleIdsJson, true);
            if (is_array($responsibleIds) && !empty($responsibleIds)) {
                $responsibleUserIdsData = $responsibleIds;
            }
            // Временно удаляем из POST, чтобы load() не пытался его загрузить
            unset($_POST['Task']['responsible_user_ids']);
        }

        if ($model->load(Yii::$app->request->post())) {
            // Обрабатываем подзадачи
            if (isset($_POST['subtasks']) && is_array($_POST['subtasks'])) {
                $subtasks = [];
                $completedFlags = isset($_POST['subtask_completed']) && is_array($_POST['subtask_completed']) 
                    ? $_POST['subtask_completed'] 
                    : [];
                
                foreach ($_POST['subtasks'] as $index => $subtaskText) {
                    $subtaskText = trim($subtaskText);
                    if (!empty($subtaskText)) {
                        $completed = isset($completedFlags[$index]) && $completedFlags[$index] == '1';
                        $subtasks[] = [
                            'text' => $subtaskText,
                            'completed' => $completed
                        ];
                    }
                }
                $model->subtasks = $subtasks;
            } else {
                // Если подзадач нет в POST, но они были в модели, сохраняем их
                if (empty($model->subtasks)) {
                    $model->subtasks = [];
                }
            }
            
            // Конвертируем даты из строк в UTCDateTime
            if (!empty($_POST['Task']['start_date'])) {
                $model->start_date = new \MongoDB\BSON\UTCDateTime(strtotime($_POST['Task']['start_date']) * 1000);
            } else {
                $model->start_date = null;
            }
            if (!empty($_POST['Task']['due_date'])) {
                $model->due_date = new \MongoDB\BSON\UTCDateTime(strtotime($_POST['Task']['due_date']) * 1000);
            } else {
                $model->due_date = null;
            }
            // Этап из ТЗ
            if (isset($_POST['Task']['milestone_index']) && $_POST['Task']['milestone_index'] !== '') {
                $model->milestone_index = (int)$_POST['Task']['milestone_index'];
            } else {
                $model->milestone_index = null;
            }
            // Обрабатываем исполнителей в зависимости от типа проекта
            if ($isGlobalProject) {
                // Для глобальных проектов: множественные исполнители
                $model->executor_user_ids = $executorUserIdsData;
                // Очищаем поля для обычных проектов
                $model->executor_user_from_department_id = null;
                $model->executor_subdepartment_id = null;
                $model->executor_user_from_subdepartment_id = null;
                
                // Обрабатываем ответственных (множественный выбор)
                $model->responsible_user_ids = $responsibleUserIdsData;
                // Очищаем старое поле для обратной совместимости
                $model->responsible_user_id = null;
            } else {
                // Для обычных проектов: один исполнитель ИЛИ «поиск любого» (executor_user_ids)
                $model->responsible_user_ids = null;
                $model->responsible_user_id = null;
                $project = $model->project;
                $projectDepartmentId = $project && $project->department_id ? (string)$project->department_id : null;

                if (!empty($executorUserIdsData)) {
                    // Режим «поиск любого сотрудника» при редактировании: без подразделения / своё подразделение — в задачу; другое подразделение — заявка
                    $directExecutorIds = [];
                    foreach ($executorUserIdsData as $uid) {
                        $uidStr = is_string($uid) ? $uid : (string)$uid;
                        $executorUser = User::findOne(['_id' => $uidStr]);
                        if (!$executorUser) continue;
                        if (!$executorUser->department_id) {
                            $directExecutorIds[] = $uidStr;
                        } elseif ($projectDepartmentId && (string)$executorUser->department_id === $projectDepartmentId) {
                            $directExecutorIds[] = $uidStr;
                        } else {
                            // Из другого подразделения: уже одобренные остаются в executor_user_ids (уже в задаче), новых добавляем только через заявку ниже
                            $approved = TaskExecutorRequest::findOne([
                                'task_id' => $model->_id,
                                'user_id' => $executorUser->_id,
                                'status' => TaskExecutorRequest::STATUS_APPROVED,
                            ]);
                            if ($approved) {
                                $directExecutorIds[] = $uidStr;
                            }
                        }
                    }
                    $model->executor_user_ids = $directExecutorIds;
                    $model->executor_user_from_department_id = null;
                    $model->executor_subdepartment_id = null;
                    $model->executor_user_from_subdepartment_id = null;
                } else {
                    $model->executor_user_ids = null;
                    if (!empty($_POST['Task']['executor_user_from_department_id'])) {
                        $model->executor_user_from_department_id = new \MongoDB\BSON\ObjectId($_POST['Task']['executor_user_from_department_id']);
                        $model->executor_subdepartment_id = null;
                        $model->executor_user_from_subdepartment_id = null;
                    } elseif (!empty($_POST['Task']['executor_subdepartment_id'])) {
                        $model->executor_subdepartment_id = new \MongoDB\BSON\ObjectId($_POST['Task']['executor_subdepartment_id']);
                        $model->executor_user_from_department_id = null;
                        $model->executor_user_from_subdepartment_id = null;
                    } elseif (!empty($_POST['Task']['executor_user_from_subdepartment_id'])) {
                        $model->executor_user_from_subdepartment_id = new \MongoDB\BSON\ObjectId($_POST['Task']['executor_user_from_subdepartment_id']);
                        $model->executor_user_from_department_id = null;
                        $model->executor_subdepartment_id = null;
                    } else {
                        $model->executor_user_from_department_id = null;
                        $model->executor_subdepartment_id = null;
                        $model->executor_user_from_subdepartment_id = null;
                    }
                }
            }

            // Создаём заявки на прикрепление для новых исполнителей из другого подразделения (при редактировании)
            if (!$isGlobalProject && !empty($executorUserIdsData) && $model->project && $model->project->department_id) {
                $projectDepartmentId = (string)$model->project->department_id;
                $alreadyInTask = [];
                if (is_array($model->executor_user_ids)) {
                    foreach ($model->executor_user_ids as $eid) {
                        $alreadyInTask[(string)$eid] = true;
                    }
                }
                foreach ($executorUserIdsData as $uid) {
                    $uidStr = is_string($uid) ? $uid : (string)$uid;
                    if (isset($alreadyInTask[$uidStr])) continue;
                    $executorUser = User::findOne(['_id' => $uidStr]);
                    if (!$executorUser || !$executorUser->department_id) continue;
                    if ((string)$executorUser->department_id === $projectDepartmentId) continue;
                    $existingReq = TaskExecutorRequest::findOne([
                        'task_id' => $model->_id,
                        'user_id' => $executorUser->_id,
                        'status' => TaskExecutorRequest::STATUS_PENDING,
                    ]);
                    if ($existingReq) continue;
                    $req = new TaskExecutorRequest();
                    $req->task_id = $model->_id;
                    $req->user_id = $executorUser->_id;
                    $req->status = TaskExecutorRequest::STATUS_PENDING;
                    $req->requested_by = $user->_id;
                    $req->save(false);
                }
            }
            
            // Исполнитель может редактировать только определенные поля (статус, прогресс, описание)
            if ($user->role === User::ROLE_EXECUTOR && $model->isAssignedToUser($user)) {
                $oldModel = Task::findOne(['_id' => $model->_id]);
                $model->title = $oldModel->title;
                $model->priority = $oldModel->priority;
                $model->executor_user_from_department_id = $oldModel->executor_user_from_department_id;
                $model->executor_subdepartment_id = $oldModel->executor_subdepartment_id;
                $model->executor_user_from_subdepartment_id = $oldModel->executor_user_from_subdepartment_id;
                $model->creator_id = $oldModel->creator_id;
                $model->project_id = $oldModel->project_id;
            }
            
            // Глоб. исполнитель: только статус, прогресс, подзадачи (как обычный исполнитель)
            $userGlobalRole = $model->project && $model->project->isGlobal() ? GlobalProjectRole::getUserRole($user->_id) : null;
            if ($userGlobalRole === GlobalProjectRole::ROLE_GLOBAL_EXECUTOR && $model->isAssignedToUser($user)) {
                $oldModel = Task::findOne(['_id' => $model->_id]);
                $model->title = $oldModel->title;
                $model->priority = $oldModel->priority;
                $model->description = $oldModel->description;
                $model->executor_user_ids = $oldModel->executor_user_ids ?? [];
                $model->responsible_user_ids = $oldModel->responsible_user_ids ?? [];
                $model->executor_user_from_department_id = $oldModel->executor_user_from_department_id;
                $model->executor_subdepartment_id = $oldModel->executor_subdepartment_id;
                $model->executor_user_from_subdepartment_id = $oldModel->executor_user_from_subdepartment_id;
                $model->creator_id = $oldModel->creator_id;
                $model->project_id = $oldModel->project_id;
                $model->start_date = $oldModel->start_date;
                $model->due_date = $oldModel->due_date;
            }
            
            // Менеджер может редактировать задачи, но не может менять назначение (только при создании)
            if ($user->role === User::ROLE_MANAGER && !$model->isNewRecord) {
                // Менеджер может редактировать все поля, включая назначение исполнителя
                // (ничего не ограничиваем здесь, так как менеджер имеет полный доступ к редактированию)
            }
            
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Задача успешно обновлена.');
                // Редирект на саму задачу
                return $this->redirect(['view', 'id' => (string)$model->_id]);
            }
        }

        return $this->render('update', [
            'model' => $model,
            'project' => $model->project,
            'milestones' => $milestones,
        ]);
    }

    /**
     * Deletes an existing Task model.
     * If deletion is successful, the browser will be redirected to the project view page.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        $user = Yii::$app->user->identity;
        $isGlobalProject = $model->project->isGlobal();
        
        // Для глобального проекта: админ, глоб. руководитель, глоб. топ-менеджер, глоб. менеджер
        // Глоб. исполнитель НЕ может удалять задачи
        if ($isGlobalProject) {
            $userGlobalRole = GlobalProjectRole::getUserRole($user->_id);
            $canDeleteGlobal = $user->role === User::ROLE_ADMIN || 
                in_array($userGlobalRole, [
                    GlobalProjectRole::ROLE_RECTOR,
                    GlobalProjectRole::ROLE_GLOBAL_TOP_MANAGER,
                    GlobalProjectRole::ROLE_GLOBAL_MANAGER,
                ]);
            if (!$canDeleteGlobal) {
                Yii::$app->session->setFlash('error', 'Вы не можете удалять задачи в глобальном проекте.');
                $project = $model->project;
                return $this->redirect($project->direction_id ? ['/direction/view', 'id' => (string)$project->direction_id] : ['global-project/view', 'id' => (string)$model->project_id]);
            }
        } else {
            // Руководитель, топ-менеджер, менеджер и админ могут удалять задачи
            if (!in_array($user->role, [User::ROLE_HEAD, User::ROLE_TOP_MANAGER, User::ROLE_MANAGER, User::ROLE_ADMIN])) {
                Yii::$app->session->setFlash('error', 'Вы не можете удалять задачи.');
                return $this->redirect(['project/view', 'id' => (string)$model->project_id]);
            }
            
            // Проверяем доступ к проекту (по подразделению)
            if ($user->role !== User::ROLE_ADMIN) {
                if (!$model->project->department_id || !$user->department_id || 
                    (string)$model->project->department_id !== (string)$user->department_id) {
                    Yii::$app->session->setFlash('error', 'Вы не можете удалять задачи проектов других подразделений.');
                    return $this->redirect(['project/view', 'id' => (string)$model->project_id]);
                }
            }
        }
        
        $projectId = (string)$model->project_id;
        $isGlobal = $model->project->isGlobal();
        $model->delete();
        Yii::$app->session->setFlash('success', 'Задача успешно удалена.');

        // Редирект зависит от типа проекта
        if ($isGlobal) {
            return $this->redirect(['global-project/view', 'id' => $projectId]);
        } else {
            return $this->redirect(['project/view', 'id' => $projectId]);
        }
    }

    /**
     * Изменяет статус задачи (AJAX)
     * @param string $id
     * @param string $status
     * @return mixed
     */
    public function actionChangeStatus($id, $status)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $model = $this->findModel($id);
        $user = Yii::$app->user->identity;
        
        // Проверка прав - Ректор может только просматривать
        if ($user->role === User::ROLE_RECTOR) {
            return ['success' => false, 'message' => 'Ректор может только просматривать задачи.'];
        }
        
        // Проверка валидности статуса
        $validStatuses = [
            Task::STATUS_TODO,
            Task::STATUS_IN_PROGRESS,
            Task::STATUS_REVIEW,
            Task::STATUS_DONE,
            Task::STATUS_CANCELED,
        ];
        
        if (!in_array($status, $validStatuses)) {
            return ['success' => false, 'message' => 'Неверный статус.'];
        }
        
        // Проверяем, является ли проект глобальным
        $isGlobalProject = $model->project && $model->project->isGlobal();
        
        // В глобальных проектах: руководитель/топ-менеджер/менеджер могут менять статус любых задач
        // Глоб. исполнитель - только своих. В обычных: исполнитель - только своих.
        if ($isGlobalProject) {
            $userGlobalRole = GlobalProjectRole::getUserRole($user->_id);
            $canChangeAny = $user->role === User::ROLE_ADMIN || in_array($userGlobalRole, [
                GlobalProjectRole::ROLE_RECTOR, GlobalProjectRole::ROLE_GLOBAL_TOP_MANAGER, GlobalProjectRole::ROLE_GLOBAL_MANAGER,
            ]);
            if (!$canChangeAny && !$model->isAssignedToUser($user)) {
                return ['success' => false, 'message' => 'Вы можете менять статус только своих задач.'];
            }
        } elseif ($user->role === User::ROLE_EXECUTOR) {
            if (!$model->isAssignedToUser($user)) {
                return ['success' => false, 'message' => 'Вы можете менять статус только своих задач.'];
            }
        }
        
        // Руководитель, топ-менеджер и менеджер могут отправить на доработку (из review в todo или in_progress)
        if (in_array($user->role, [User::ROLE_HEAD, User::ROLE_TOP_MANAGER, User::ROLE_MANAGER]) && 
            $model->status === Task::STATUS_REVIEW) {
            if ($status !== Task::STATUS_TODO && $status !== Task::STATUS_IN_PROGRESS) {
                return ['success' => false, 'message' => 'Вы можете отправить задачу на доработку (todo или in_progress).'];
            }
        }
        
        $model->status = $status;
        
        // Автоматически обновляем прогресс при изменении статуса
        if ($status === Task::STATUS_DONE) {
            $model->progress = 100;
        } elseif ($status === Task::STATUS_TODO) {
            $model->progress = 0;
        } elseif ($status === Task::STATUS_IN_PROGRESS && $model->progress == 0) {
            $model->progress = 10;
        } elseif ($status === Task::STATUS_REVIEW && $model->progress < 90) {
            $model->progress = 90;
        }
        
        if ($model->save(false)) {
            return ['success' => true, 'message' => 'Статус успешно изменен.'];
        } else {
            return ['success' => false, 'message' => 'Ошибка при сохранении.'];
        }
    }

    /**
     * Displays a single Task model.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        $user = Yii::$app->user->identity;
        
        // Проверка доступа
        $this->checkViewAccess($model, $user);
        
        // Заявки на прикрепление (для блока «Ожидание одобрения / Отклонённые»)
        $executorRequests = TaskExecutorRequest::getAllByTask($model->_id);
        
        return $this->render('view', [
            'model' => $model,
            'executorRequests' => $executorRequests,
        ]);
    }

    /**
     * Переключает статус подзадачи (AJAX)
     * @return mixed
     */
    public function actionToggleSubtask()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        try {
            $taskId = Yii::$app->request->post('task_id');
            $subtaskIndex = Yii::$app->request->post('subtask_index');
            $completed = Yii::$app->request->post('completed') == '1';
            
            if (!$taskId) {
                return ['success' => false, 'message' => 'Не указан ID задачи.'];
            }
            
            if ($subtaskIndex === null || $subtaskIndex === '') {
                return ['success' => false, 'message' => 'Не указан индекс подзадачи.'];
            }
            
            $subtaskIndex = (int)$subtaskIndex;
            
            $model = $this->findModel($taskId);
            $user = Yii::$app->user->identity;
            
            if (!$user) {
                return ['success' => false, 'message' => 'Пользователь не авторизован.'];
            }
            
            // Проверка прав доступа
            try {
                $this->checkViewAccess($model, $user);
            } catch (\Exception $e) {
                return ['success' => false, 'message' => $e->getMessage()];
            }
            
            // Проверяем, что пользователь может редактировать задачу
            if ($user->role === User::ROLE_RECTOR) {
                return ['success' => false, 'message' => 'Ректор может только просматривать задачи.'];
            }
            
            // Исполнитель и глоб. исполнитель могут изменять подзадачи только своих задач
            $userGlobalRole = $model->project && $model->project->isGlobal() ? GlobalProjectRole::getUserRole($user->_id) : null;
            $isExecutor = $user->role === User::ROLE_EXECUTOR || $userGlobalRole === GlobalProjectRole::ROLE_GLOBAL_EXECUTOR;
            if ($isExecutor && !$model->isAssignedToUser($user)) {
                return ['success' => false, 'message' => 'Вы можете изменять подзадачи только своих задач.'];
            }
            
            // Обновляем статус подзадачи
            if (!is_array($model->subtasks)) {
                $model->subtasks = [];
            }
            
            if (!isset($model->subtasks[$subtaskIndex])) {
                return ['success' => false, 'message' => 'Подзадача с индексом ' . $subtaskIndex . ' не найдена. Всего подзадач: ' . count($model->subtasks)];
            }
            
            // Обновляем статус подзадачи в массиве
            $subtasks = $model->subtasks;
            $subtasks[$subtaskIndex]['completed'] = (bool)$completed;
            
            // Используем прямое обновление через коллекцию MongoDB для надежного сохранения вложенных массивов
            $collection = \Yii::$app->mongodb->getCollection('tasks');
            $updateResult = $collection->update(
                ['_id' => $model->_id],
                [
                    '$set' => [
                        'subtasks' => $subtasks,
                        'updated_at' => new \MongoDB\BSON\UTCDateTime()
                    ]
                ]
            );
            
            if ($updateResult) {
                // Перезагружаем модель из БД
                $model->refresh();
                
                // Пересчитываем прогресс
                $total = $model->getTotalSubtasksCount();
                if ($total > 0) {
                    $newProgress = $model->calculateProgress();
                    // Обновляем прогресс тоже через прямое обновление
                    $collection->update(
                        ['_id' => $model->_id],
                        ['$set' => ['progress' => $newProgress]]
                    );
                    $model->refresh();
                }
                
                return [
                    'success' => true, 
                    'message' => 'Статус подзадачи изменен.',
                    'progress' => $model->progress,
                    'progress_format' => $model->getProgressFormat()
                ];
            } else {
                return ['success' => false, 'message' => 'Ошибка при обновлении в базе данных.'];
            }
        } catch (\Exception $e) {
            Yii::error('Ошибка в actionToggleSubtask: ' . $e->getMessage(), 'task');
            return [
                'success' => false, 
                'message' => 'Ошибка: ' . $e->getMessage(),
                'trace' => YII_DEBUG ? $e->getTraceAsString() : null
            ];
        }
    }

    /**
     * Архивирует задачу
     * @param string $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionArchive($id)
    {
        $model = $this->findModel($id);
        $user = Yii::$app->user->identity;
        
        // Проверка прав доступа
        $this->checkAccess($model, $user);
        
        // Архивировать можно только выполненные задачи
        if ($model->status !== Task::STATUS_DONE) {
            Yii::$app->session->setFlash('error', 'Архивировать можно только выполненные задачи.');
            return $this->redirect(['view', 'id' => $id]);
        }
        
        $model->is_archived = true;
        if ($model->save(false)) {
            Yii::$app->session->setFlash('success', 'Задача успешно архивирована.');
        } else {
            Yii::$app->session->setFlash('error', 'Ошибка при архивации задачи.');
        }
        
        return $this->redirect(['project/view', 'id' => (string)$model->project_id]);
    }

    /**
     * Разархивирует задачу
     * @param string $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUnarchive($id)
    {
        $model = $this->findModel($id);
        $user = Yii::$app->user->identity;
        
        // Проверка прав доступа
        $this->checkAccess($model, $user);
        
        $model->is_archived = false;
        if ($model->save(false)) {
            Yii::$app->session->setFlash('success', 'Задача успешно разархивирована.');
        } else {
            Yii::$app->session->setFlash('error', 'Ошибка при разархивации задачи.');
        }
        
        return $this->redirect(['project/archive', 'id' => (string)$model->project_id]);
    }

    /**
     * Finds the Task model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return Task the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        $idObj = is_string($id) ? new \MongoDB\BSON\ObjectId($id) : $id;
        if (($model = Task::findOne(['_id' => $idObj])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('Задача не найдена.');
    }

    /**
     * Проверка прав доступа для редактирования/удаления
     */
    protected function checkAccess($model, $user)
    {
        // Админ имеет полный доступ
        if ($user->role === User::ROLE_ADMIN) {
            return;
        }
        
        // Проверяем, является ли проект глобальным
        $isGlobalProject = $model->project && $model->project->isGlobal();
        
        if ($isGlobalProject) {
            $userGlobalRole = \app\models\GlobalProjectRole::getUserRole($user->_id);
            // Полное редактирование: админ, глоб. руководитель, глоб. топ-менеджер, глоб. менеджер
            if ($user->role === User::ROLE_ADMIN || 
                in_array($userGlobalRole, [
                    \app\models\GlobalProjectRole::ROLE_RECTOR,
                    \app\models\GlobalProjectRole::ROLE_GLOBAL_TOP_MANAGER,
                    \app\models\GlobalProjectRole::ROLE_GLOBAL_MANAGER,
                ])) {
                return;
            }
            // Глоб. исполнитель: только просмотр и выполнение (ограниченное редактирование в actionUpdate)
            if ($userGlobalRole === \app\models\GlobalProjectRole::ROLE_GLOBAL_EXECUTOR && $model->isAssignedToUser($user)) {
                return;
            }
            throw new NotFoundHttpException('У вас нет прав для выполнения этого действия.');
        } else {
            // Руководитель может редактировать задачи проектов своего подразделения
            if ($user->role === User::ROLE_HEAD && 
                $model->project && $model->project->department_id && $user->department_id &&
                (string)$model->project->department_id === (string)$user->department_id) {
                return;
            }
            
            // Топ-менеджер может редактировать задачи проектов своего подразделения
            if ($user->role === User::ROLE_TOP_MANAGER && 
                $model->project && $model->project->department_id && $user->department_id &&
                (string)$model->project->department_id === (string)$user->department_id) {
                return;
            }
            
            // Менеджер может редактировать задачи проектов своего подразделения
            if ($user->role === User::ROLE_MANAGER && 
                $model->project && $model->project->department_id && $user->department_id &&
                (string)$model->project->department_id === (string)$user->department_id) {
                return;
            }
            
            // Исполнитель может редактировать только свои задачи (статус, прогресс, описание)
            if ($user->role === User::ROLE_EXECUTOR && $model->isAssignedToUser($user)) {
                return;
            }
        }
        
        throw new NotFoundHttpException('У вас нет прав для выполнения этого действия.');
    }

    /**
     * Проверка прав доступа для просмотра
     */
    protected function checkViewAccess($model, $user)
    {
        // Админ имеет полный доступ
        if ($user->role === User::ROLE_ADMIN) {
            return;
        }
        
        // Проверяем, является ли проект глобальным
        $isGlobalProject = $model->project && $model->project->isGlobal();
        
        if ($isGlobalProject) {
            $userGlobalRole = GlobalProjectRole::getUserRole($user->_id);
            // Видят все задачи: админ, глоб. руководитель, глоб. топ-менеджер, глоб. менеджер
            if ($user->role === User::ROLE_ADMIN || 
                in_array($userGlobalRole, [
                    GlobalProjectRole::ROLE_RECTOR,
                    GlobalProjectRole::ROLE_GLOBAL_TOP_MANAGER,
                    GlobalProjectRole::ROLE_GLOBAL_MANAGER,
                ])) {
                return;
            }
            // Глоб. исполнитель видит только свои задачи
            if ($model->isAssignedToUser($user)) {
                return;
            }
        } else {
            // Ректор имеет доступ ко всем задачам (только просмотр)
            if ($user->role === User::ROLE_RECTOR) {
                return;
            }
            
            // Прикреплённые к задаче (исполнители, в т.ч. по заявке из другого подразделения) видят задачу
            if ($model->isAssignedToUser($user)) {
                return;
            }
            
            // Руководитель имеет доступ к задачам проектов своего подразделения
            if ($user->role === User::ROLE_HEAD && 
                $model->project && $model->project->department_id && $user->department_id &&
                (string)$model->project->department_id === (string)$user->department_id) {
                return;
            }
            
            // Топ-менеджер имеет доступ к задачам проектов своего подразделения
            if ($user->role === User::ROLE_TOP_MANAGER && 
                $model->project && $model->project->department_id && $user->department_id &&
                (string)$model->project->department_id === (string)$user->department_id) {
                return;
            }
            
            // Менеджер имеет доступ к задачам проектов своего подразделения
            if ($user->role === User::ROLE_MANAGER && 
                $model->project && $model->project->department_id && $user->department_id &&
                (string)$model->project->department_id === (string)$user->department_id) {
                return;
            }
            
            // Исполнитель имеет доступ только если он назначен на задачу
            if ($user->role === User::ROLE_EXECUTOR && $model->isAssignedToUser($user)) {
                return;
            }
        }
        
        throw new NotFoundHttpException('У вас нет прав для просмотра этой задачи.');
    }

    /**
     * Поиск пользователей для назначения исполнителей в глобальных проектах (AJAX)
     * Исключает админов
     * @return array
     */
    public function actionSearchUsers()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $query = trim(Yii::$app->request->get('q', ''));
        $excludeDepartmentId = trim(Yii::$app->request->get('exclude_department_id', ''));
        $limit = 20;
        
        if (empty($query) || strlen($query) < 3) {
            return ['results' => []];
        }
        
        try {
            // Используем регулярное выражение для поиска
            $escapedQuery = preg_quote($query, '/');
            $regex = new \MongoDB\BSON\Regex($escapedQuery, 'i');
            
            $andConditions = [
                [
                    '$or' => [
                        ['fio' => $regex],
                        ['email' => $regex]
                    ]
                ],
                ['role' => ['$ne' => User::ROLE_ADMIN]]
            ];
            
            // Исключить пользователей из указанного подразделения (и его департаментов)
            if ($excludeDepartmentId !== '') {
                try {
                    $excludeOid = new \MongoDB\BSON\ObjectId($excludeDepartmentId);
                } catch (\Exception $e) {
                    $excludeOid = null;
                }
                if ($excludeOid) {
                    $subDepts = Department::find()
                        ->where(['parent_id' => $excludeOid])
                        ->select(['_id'])
                        ->all();
                    $subDeptOids = array_map(function ($d) {
                        return $d->_id;
                    }, $subDepts);
                    $andConditions[] = ['department_id' => ['$ne' => $excludeOid]];
                    $andConditions[] = [
                        '$or' => [
                            ['subdepartment_id' => null],
                            ['subdepartment_id' => ['$nin' => $subDeptOids]]
                        ]
                    ];
                }
            }
            
            $users = User::find()
                ->where(['$and' => $andConditions])
                ->orderBy(['fio' => SORT_ASC])
                ->limit($limit)
                ->all();
            
            $results = [];
            foreach ($users as $user) {
                if ($user->fio && $user->email) {
                    $results[] = [
                        'id' => (string)$user->_id,
                        'text' => $user->fio . ' (' . $user->email . ')',
                    ];
                }
            }
            
            return ['results' => $results];
        } catch (\Exception $e) {
            Yii::error('Error searching users: ' . $e->getMessage());
            Yii::error('Stack trace: ' . $e->getTraceAsString());
            return [
                'results' => [],
                'error' => YII_DEBUG ? $e->getMessage() : 'Ошибка при поиске пользователей'
            ];
        }
    }

    /**
     * Поиск пользователей для назначения ответственными в глобальных проектах (AJAX)
     * Ищет всех пользователей (как actionSearchUsers), исключая админов
     * @return array
     */
    public function actionSearchGlobalManagers()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $query = trim(Yii::$app->request->get('q', ''));
        $limit = 20;
        
        if (empty($query) || strlen($query) < 2) {
            return ['results' => []];
        }
        
        try {
            // Используем регулярное выражение для поиска
            $escapedQuery = preg_quote($query, '/');
            $regex = new \MongoDB\BSON\Regex($escapedQuery, 'i');
            
            // Поиск по ФИО или email, исключая админов (как в actionSearchUsers)
            $users = User::find()
                ->where([
                    '$and' => [
                        [
                            '$or' => [
                                ['fio' => $regex],
                                ['email' => $regex]
                            ]
                        ],
                        ['role' => ['$ne' => User::ROLE_ADMIN]]
                    ]
                ])
                ->orderBy(['fio' => SORT_ASC])
                ->limit($limit)
                ->all();
            
            $results = [];
            foreach ($users as $user) {
                if ($user->fio && $user->email) {
                    $results[] = [
                        'id' => (string)$user->_id,
                        'text' => $user->fio . ' (' . $user->email . ')',
                    ];
                }
            }
            
            return ['results' => $results];
        } catch (\Exception $e) {
            Yii::error('Error searching users for responsible: ' . $e->getMessage());
            Yii::error('Stack trace: ' . $e->getTraceAsString());
            return [
                'results' => [],
                'error' => YII_DEBUG ? $e->getMessage() : 'Ошибка при поиске пользователей'
            ];
        }
    }
}

