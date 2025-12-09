<?php

namespace app\controllers;

use Yii;
use app\models\Task;
use app\models\Project;
use app\models\User;
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
        
        // Только руководитель проекта, админ или ректор могут создавать задачи
        if ($user->role !== User::ROLE_MANAGER && 
            $user->role !== User::ROLE_ADMIN && 
            $user->role !== User::ROLE_RECTOR) {
            Yii::$app->session->setFlash('error', 'Только руководитель может создавать задачи.');
            return $this->redirect(['project/view', 'id' => $project_id]);
        }
        
        // Если менеджер, проверяем что это его проект
        if ($user->role === User::ROLE_MANAGER && (string)$project->manager_id !== (string)$user->_id) {
            Yii::$app->session->setFlash('error', 'Вы не можете создавать задачи для этого проекта.');
            return $this->redirect(['project/view', 'id' => $project_id]);
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

        if ($model->load(Yii::$app->request->post())) {
            // ВАЖНО: После load() нужно снова установить project_id и creator_id,
            // так как они могут быть перезаписаны из POST данных
            $model->project_id = $projectIdObj;
            $model->creator_id = $user->_id;
            
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
            // Конвертируем executor_id в ObjectId если это строка
            if (!empty($_POST['Task']['executor_id'])) {
                $model->executor_id = new \MongoDB\BSON\ObjectId($_POST['Task']['executor_id']);
            } else {
                $model->executor_id = null;
            }
            
            // Валидируем модель
            if ($model->validate()) {
                if ($model->save(false)) { // false - чтобы не валидировать повторно
                    Yii::$app->session->setFlash('success', 'Задача успешно создана.');
                    return $this->redirect(['project/view', 'id' => $project_id]);
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

        // Получаем исполнителей проекта
        $executors = [];
        if ($project->executors) {
            foreach ($project->executors as $executorId) {
                $executor = User::findOne(['_id' => $executorId]);
                if ($executor) {
                    $executors[] = $executor;
                }
            }
        }

        return $this->render('create', [
            'model' => $model,
            'project' => $project,
            'executors' => $executors,
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

        if ($model->load(Yii::$app->request->post())) {
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
            // Конвертируем executor_id в ObjectId если это строка
            if (!empty($_POST['Task']['executor_id'])) {
                $model->executor_id = new \MongoDB\BSON\ObjectId($_POST['Task']['executor_id']);
            } else {
                $model->executor_id = null;
            }
            
            // Исполнитель может редактировать только определенные поля
            if ($user->role === User::ROLE_EXECUTOR && (string)$model->executor_id === (string)$user->_id) {
                // Исполнитель может менять только статус, прогресс и описание
                $oldModel = Task::findOne(['_id' => $model->_id]);
                $model->title = $oldModel->title;
                $model->priority = $oldModel->priority;
                $model->executor_id = $oldModel->executor_id;
                $model->creator_id = $oldModel->creator_id;
                $model->project_id = $oldModel->project_id;
            }
            
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Задача успешно обновлена.');
                return $this->redirect(['project/view', 'id' => (string)$model->project_id]);
            }
        }

        // Получаем исполнителей проекта
        $project = $model->project;
        $executors = [];
        if ($project && $project->executors) {
            foreach ($project->executors as $executorId) {
                $executor = User::findOne(['_id' => $executorId]);
                if ($executor) {
                    $executors[] = $executor;
                }
            }
        }

        return $this->render('update', [
            'model' => $model,
            'project' => $project,
            'executors' => $executors,
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
        
        // Только руководитель проекта, админ или ректор могут удалять задачи
        if ($user->role !== User::ROLE_MANAGER && 
            $user->role !== User::ROLE_ADMIN && 
            $user->role !== User::ROLE_RECTOR) {
            Yii::$app->session->setFlash('error', 'Вы не можете удалять задачи.');
            return $this->redirect(['project/view', 'id' => (string)$model->project_id]);
        }
        
        // Если менеджер, проверяем что это его проект
        if ($user->role === User::ROLE_MANAGER && (string)$model->project->manager_id !== (string)$user->_id) {
            Yii::$app->session->setFlash('error', 'Вы не можете удалять задачи этого проекта.');
            return $this->redirect(['project/view', 'id' => (string)$model->project_id]);
        }
        
        $projectId = (string)$model->project_id;
        $model->delete();
        Yii::$app->session->setFlash('success', 'Задача успешно удалена.');

        return $this->redirect(['project/view', 'id' => $projectId]);
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
        
        // Проверка прав
        if ($user->role === User::ROLE_RECTOR) {
            // Ректор только просматривает
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
        
        // Исполнитель может менять статус только своих задач
        if ($user->role === User::ROLE_EXECUTOR) {
            if (!$model->executor_id || (string)$model->executor_id !== (string)$user->_id) {
                return ['success' => false, 'message' => 'Вы можете менять статус только своих задач.'];
            }
        }
        
        // Руководитель может отправить на доработку (из review в todo или in_progress)
        if ($user->role === User::ROLE_MANAGER && $model->status === Task::STATUS_REVIEW) {
            if ($status !== Task::STATUS_TODO && $status !== Task::STATUS_IN_PROGRESS) {
                return ['success' => false, 'message' => 'Руководитель может отправить задачу на доработку (todo или in_progress).'];
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
        
        return $this->render('view', [
            'model' => $model,
        ]);
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
        // Админ и ректор имеют полный доступ
        if ($user->role === User::ROLE_ADMIN || $user->role === User::ROLE_RECTOR) {
            return;
        }
        
        // Руководитель проекта может редактировать задачи своего проекта
        if ($user->role === User::ROLE_MANAGER && 
            $model->project && 
            (string)$model->project->manager_id === (string)$user->_id) {
            return;
        }
        
        // Исполнитель может редактировать только свои задачи
        if ($user->role === User::ROLE_EXECUTOR && 
            $model->executor_id && 
            (string)$model->executor_id === (string)$user->_id) {
            return;
        }
        
        throw new NotFoundHttpException('У вас нет прав для выполнения этого действия.');
    }

    /**
     * Проверка прав доступа для просмотра
     */
    protected function checkViewAccess($model, $user)
    {
        // Админ, ректор и руководитель проекта имеют доступ
        if ($user->role === User::ROLE_ADMIN || 
            $user->role === User::ROLE_RECTOR ||
            ($user->role === User::ROLE_MANAGER && 
             $model->project && 
             (string)$model->project->manager_id === (string)$user->_id)) {
            return;
        }
        
        // Исполнитель имеет доступ только если он назначен на задачу
        if ($user->role === User::ROLE_EXECUTOR && 
            $model->executor_id && 
            (string)$model->executor_id === (string)$user->_id) {
            return;
        }
        
        throw new NotFoundHttpException('У вас нет прав для просмотра этой задачи.');
    }
}

