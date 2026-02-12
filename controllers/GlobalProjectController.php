<?php

namespace app\controllers;

use Yii;
use app\models\Project;
use app\models\ProjectDocument;
use app\models\ProjectSpec;
use app\models\Task;
use app\models\GlobalProjectRole;
use app\models\User;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\web\Response;

/**
 * GlobalProjectController implements the CRUD actions for Global Project model.
 */
class GlobalProjectController extends Controller
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
     * Displays the global project.
     * Redirects to direction index (new architecture).
     * @return mixed
     */
    public function actionIndex()
    {
        // Перенаправляем на страницу направлений (новая архитектура)
        return $this->redirect(['/direction/index']);
    }

    /**
     * Displays a single Global Project model.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        
        // Проверяем, что это глобальный проект
        if (!$model->isGlobal()) {
            throw new NotFoundHttpException('Проект не найден.');
        }
        
        // В глобальных проектах все пользователи имеют доступ по умолчанию
        // (не требуется проверка роли для просмотра)
        
        // Загружаем ТЗ для проекта
        $spec = ProjectSpec::findOne(['project_id' => $model->_id]);
        
        // Проверяем роль текущего пользователя в глобальном проекте
        $user = Yii::$app->user->identity;
        $userGlobalRole = GlobalProjectRole::getUserRole($user->_id);

        $documents = ProjectDocument::find()
            ->where(['project_id' => $model->_id])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        // Upload/delete only for admin and global TOP (rector can only view)
        $canManageDocuments = $user->role === User::ROLE_ADMIN || $userGlobalRole === GlobalProjectRole::ROLE_GLOBAL_TOP_MANAGER;
        
        return $this->render('view', [
            'model' => $model,
            'spec' => $spec,
            'userGlobalRole' => $userGlobalRole,
            'documents' => $documents,
            'canManageDocuments' => $canManageDocuments,
        ]);
    }

    /**
     * Updates an existing Global Project model.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        
        // Проверяем, что это глобальный проект
        if (!$model->isGlobal()) {
            throw new NotFoundHttpException('Проект не найден.');
        }
        
        $user = Yii::$app->user->identity;
        
        // Только админ, глоб. руководитель или глоб. топ-менеджер может редактировать
        $userGlobalRole = GlobalProjectRole::getUserRole($user->_id);
        if ($user->role !== User::ROLE_ADMIN && 
            !in_array($userGlobalRole, [GlobalProjectRole::ROLE_RECTOR, GlobalProjectRole::ROLE_GLOBAL_TOP_MANAGER])) {
            Yii::$app->session->setFlash('error', 'У вас нет прав для редактирования глобального проекта.');
            return $this->redirect(['view', 'id' => $id]);
        }
        
        if ($model->load(Yii::$app->request->post())) {
            // Убеждаемся, что проект остается глобальным
            $model->department_id = null;
            
            // Обрабатываем даты
            if (!empty($_POST['Project']['start_date_str'])) {
                $model->start_date = new \MongoDB\BSON\UTCDateTime(strtotime($_POST['Project']['start_date_str']) * 1000);
            }
            if (!empty($_POST['Project']['end_date_str'])) {
                $model->end_date = new \MongoDB\BSON\UTCDateTime(strtotime($_POST['Project']['end_date_str']) * 1000);
            }
            
            // Обрабатываем массив руководителей для глобального проекта
            $managerIds = [];
            if (isset($_POST['Project']['manager_ids_array'])) {
                $managerIdsArray = $_POST['Project']['manager_ids_array'];
                
                // Если это строка с ID, разделенными запятыми
                if (is_string($managerIdsArray) && !empty($managerIdsArray)) {
                    $ids = explode(',', $managerIdsArray);
                    foreach ($ids as $managerId) {
                        $managerId = trim($managerId);
                        if (!empty($managerId)) {
                            try {
                                $managerIds[] = new \MongoDB\BSON\ObjectId($managerId);
                            } catch (\Exception $e) {
                                // Пропускаем невалидные ID
                            }
                        }
                    }
                } 
                // Если это массив
                elseif (is_array($managerIdsArray)) {
                    foreach ($managerIdsArray as $managerId) {
                        if (!empty($managerId)) {
                            try {
                                $managerIds[] = new \MongoDB\BSON\ObjectId($managerId);
                            } catch (\Exception $e) {
                                // Пропускаем невалидные ID
                            }
                        }
                    }
                }
            }
            
            $model->manager_ids = $managerIds;
            // Для обратной совместимости устанавливаем первого руководителя как manager_id
            if (!empty($managerIds)) {
                $model->manager_id = $managerIds[0];
            } else {
                $model->manager_id = null;
            }
            
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Глобальный проект успешно обновлен.');
                return $this->redirect(['view', 'id' => $id]);
            }
        }
        
        // Конвертируем даты для формы
        if ($model->start_date instanceof \MongoDB\BSON\UTCDateTime) {
            $model->start_date_str = date('Y-m-d', $model->start_date->toDateTime()->getTimestamp());
        }
        if ($model->end_date instanceof \MongoDB\BSON\UTCDateTime) {
            $model->end_date_str = date('Y-m-d', $model->end_date->toDateTime()->getTimestamp());
        }
        
        // Подготавливаем массив выбранных руководителей для формы
        $model->manager_ids_array = [];
        if (!empty($model->manager_ids)) {
            foreach ($model->manager_ids as $id) {
                if ($id instanceof \MongoDB\BSON\ObjectId) {
                    $model->manager_ids_array[] = (string)$id;
                } elseif (is_string($id)) {
                    $model->manager_ids_array[] = $id;
                }
            }
        } elseif ($model->manager_id) {
            // Для обратной совместимости
            $model->manager_ids_array[] = (string)$model->manager_id;
        }
        
        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Displays kanban board for global project tasks
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionKanban($id)
    {
        $model = $this->findModel($id);
        
        // Проверяем, что это глобальный проект
        if (!$model->isGlobal()) {
            throw new NotFoundHttpException('Проект не найден.');
        }
        
        // В глобальных проектах все пользователи имеют доступ по умолчанию
        // (не требуется проверка роли для просмотра)
        
        // Используем view из project, так как он уже поддерживает глобальные проекты
        return $this->render('@app/views/project/kanban', [
            'model' => $model,
        ]);
    }

    /**
     * Displays list of all tasks for global project
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionTasksList($id)
    {
        $model = $this->findModel($id);
        
        // Проверяем, что это глобальный проект
        if (!$model->isGlobal()) {
            throw new NotFoundHttpException('Проект не найден.');
        }
        
        $user = Yii::$app->user->identity;
        $userGlobalRole = GlobalProjectRole::getUserRole($user->_id);
        
        // Получение задач (исключаем архивные)
        $tasksQuery = Task::find()
            ->where(['project_id' => $model->_id])
            ->andWhere(['$or' => [
                ['is_archived' => false],
                ['is_archived' => ['$exists' => false]],
            ]])
            ->orderBy(['created_at' => SORT_DESC]);
        
        // Фильтруем задачи по видимости: глоб. руководитель, топ-менеджер, менеджер видят все, глоб. исполнитель - только свои
        if ($user->role !== User::ROLE_ADMIN && 
            !in_array($userGlobalRole, [GlobalProjectRole::ROLE_RECTOR, GlobalProjectRole::ROLE_GLOBAL_TOP_MANAGER, GlobalProjectRole::ROLE_GLOBAL_MANAGER])) {
            // Пользователь видит только задачи, на которые он назначен
            $allTasks = Task::find()
                ->where(['project_id' => $model->_id])
                ->andWhere(['$or' => [
                    ['is_archived' => false],
                    ['is_archived' => ['$exists' => false]],
                ]])
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
        
        return $this->render('tasks-list', [
            'model' => $model,
            'tasks' => $tasks,
            'userGlobalRole' => $userGlobalRole,
        ]);
    }

    /**
     * Search managers for global project
     * @return array JSON response with search results
     */
    public function actionSearchManagers()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $query = trim(Yii::$app->request->get('q', ''));
        $limit = 20;
        
        if (empty($query) || strlen($query) < 3) {
            return ['results' => []];
        }
        
        try {
            // Используем регулярное выражение для поиска
            $escapedQuery = preg_quote($query, '/');
            $regex = new \MongoDB\BSON\Regex($escapedQuery, 'i');
            
            // Поиск по ФИО или email среди пользователей с ролями руководителей
            $users = User::find()
                ->where([
                    'role' => ['$in' => [User::ROLE_ADMIN, User::ROLE_RECTOR, User::ROLE_HEAD, User::ROLE_TOP_MANAGER, User::ROLE_MANAGER]],
                    '$or' => [
                        ['fio' => $regex],
                        ['email' => $regex]
                    ]
                ])
                ->orderBy(['fio' => SORT_ASC])
                ->limit($limit)
                ->all();
            
            $results = [];
            foreach ($users as $user) {
                if ($user->fio && $user->email) {
                    $roleLabel = [
                        User::ROLE_ADMIN => 'Админ',
                        User::ROLE_RECTOR => 'Ректор',
                        User::ROLE_HEAD => 'Руководитель',
                        User::ROLE_TOP_MANAGER => 'Топ-менеджер',
                        User::ROLE_MANAGER => 'Менеджер',
                    ][$user->role] ?? $user->role;
                    
                    $results[] = [
                        'id' => (string)$user->_id,
                        'text' => $user->fio . ' (' . $user->email . ')',
                        'role' => $roleLabel,
                    ];
                }
            }
            
            return ['results' => $results];
        } catch (\Exception $e) {
            Yii::error('Error searching managers: ' . $e->getMessage());
            return [
                'results' => [],
                'error' => YII_DEBUG ? $e->getMessage() : 'Ошибка при поиске руководителей'
            ];
        }
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
        if (($model = Project::findOne(['_id' => new \MongoDB\BSON\ObjectId($id)])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('Проект не найден.');
    }
}

