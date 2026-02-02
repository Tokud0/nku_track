<?php

namespace app\controllers;

use Yii;
use app\models\Direction;
use app\models\Project;
use app\models\Task;
use app\models\GlobalProjectRole;
use app\models\User;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\web\Response;

/**
 * DirectionController implements the CRUD actions for Direction model.
 */
class DirectionController extends Controller
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
     * Lists all Direction models (main page for global project).
     * @return mixed
     */
    public function actionIndex()
    {
        $user = Yii::$app->user->identity;
        $userGlobalRole = GlobalProjectRole::getUserRole($user->_id);
        
        // Получаем все направления (активные и черновики, без архивных)
        $directions = Direction::find()
            ->where(['status' => ['$in' => [Direction::STATUS_ACTIVE, Direction::STATUS_DRAFT]]])
            ->orderBy(['sort_order' => SORT_ASC, 'title' => SORT_ASC])
            ->all();
        
        // Проверяем, может ли пользователь создавать направления
        $canCreate = $this->canManageDirections($user, $userGlobalRole);
        
        // Получаем legacy проекты (старые глобальные проекты без direction_id)
        $legacyProjects = Project::find()
            ->where(['department_id' => null])
            ->andWhere(['$or' => [
                ['direction_id' => null],
                ['direction_id' => ['$exists' => false]],
            ]])
            ->andWhere(['$or' => [
                ['is_legacy' => false],
                ['is_legacy' => ['$exists' => false]],
            ]])
            ->all();
        
        return $this->render('index', [
            'directions' => $directions,
            'canCreate' => $canCreate,
            'userGlobalRole' => $userGlobalRole,
            'legacyProjects' => $legacyProjects,
        ]);
    }

    /**
     * Displays a single Direction model with its projects.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        $user = Yii::$app->user->identity;
        $userGlobalRole = GlobalProjectRole::getUserRole($user->_id);
        
        // Получаем проекты направления
        $projects = Project::find()
            ->where(['direction_id' => $model->_id])
            ->andWhere(['$or' => [
                ['is_legacy' => false],
                ['is_legacy' => ['$exists' => false]],
            ]])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();
        
        $canManage = $model->canManage($user);
        $canCreateProject = $this->canManageProjects($user, $userGlobalRole);
        
        return $this->render('view', [
            'model' => $model,
            'projects' => $projects,
            'canManage' => $canManage,
            'canCreateProject' => $canCreateProject,
            'userGlobalRole' => $userGlobalRole,
        ]);
    }

    /**
     * Creates a new Direction model.
     * @return mixed
     */
    public function actionCreate()
    {
        $user = Yii::$app->user->identity;
        $userGlobalRole = GlobalProjectRole::getUserRole($user->_id);
        
        if (!$this->canManageDirections($user, $userGlobalRole)) {
            throw new ForbiddenHttpException('У вас нет прав для создания направлений.');
        }
        
        $model = new Direction();
        $model->status = Direction::STATUS_ACTIVE; // По умолчанию активное, чтобы сразу отображалось на главной
        
        if ($model->load(Yii::$app->request->post())) {
            // Обрабатываем массив руководителей
            $this->processManagerIds($model);
            
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Направление успешно создано.');
                return $this->redirect(['view', 'id' => (string)$model->_id]);
            }
        }
        
        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Direction model.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $user = Yii::$app->user->identity;
        
        if (!$model->canManage($user)) {
            throw new ForbiddenHttpException('У вас нет прав для редактирования направления.');
        }
        
        if ($model->load(Yii::$app->request->post())) {
            // Обрабатываем массив руководителей
            $this->processManagerIds($model);
            
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Направление успешно обновлено.');
                return $this->redirect(['view', 'id' => $id]);
            }
        }
        
        // Подготавливаем массив выбранных руководителей для формы
        $this->prepareManagerIdsForForm($model);
        
        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Direction model (archives it).
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        $user = Yii::$app->user->identity;
        
        if (!$model->canManage($user)) {
            throw new ForbiddenHttpException('У вас нет прав для удаления направления.');
        }
        
        // Проверяем, есть ли проекты в направлении
        $projectsCount = Project::find()
            ->where(['direction_id' => $model->_id])
            ->andWhere(['$or' => [
                ['is_legacy' => false],
                ['is_legacy' => ['$exists' => false]],
            ]])
            ->count();
        
        if ($projectsCount > 0) {
            // Если есть проекты, архивируем направление
            $model->status = Direction::STATUS_ARCHIVED;
            $model->save(false);
            Yii::$app->session->setFlash('warning', 'Направление содержит проекты и было архивировано.');
        } else {
            // Если проектов нет, удаляем направление
            $model->delete();
            Yii::$app->session->setFlash('success', 'Направление успешно удалено.');
        }
        
        return $this->redirect(['index']);
    }

    /**
     * Creates a new global project within the direction.
     * @param string $id Direction ID
     * @return mixed
     */
    public function actionCreateProject($id)
    {
        $direction = $this->findModel($id);
        $user = Yii::$app->user->identity;
        $userGlobalRole = GlobalProjectRole::getUserRole($user->_id);
        
        if (!$this->canManageProjects($user, $userGlobalRole)) {
            throw new ForbiddenHttpException('У вас нет прав для создания проектов.');
        }
        
        $model = new Project();
        $model->department_id = null; // Глобальный проект
        $model->direction_id = $direction->_id;
        $model->status = Project::STATUS_DRAFT;
        
        if ($model->load(Yii::$app->request->post())) {
            // Обрабатываем даты
            if (!empty($_POST['Project']['start_date_str'])) {
                $model->start_date = new \MongoDB\BSON\UTCDateTime(strtotime($_POST['Project']['start_date_str']) * 1000);
            }
            if (!empty($_POST['Project']['end_date_str'])) {
                $model->end_date = new \MongoDB\BSON\UTCDateTime(strtotime($_POST['Project']['end_date_str']) * 1000);
            }
            
            // Обрабатываем массив руководителей
            $managerIds = [];
            if (isset($_POST['Project']['manager_ids_array'])) {
                $managerIdsArray = $_POST['Project']['manager_ids_array'];
                
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
                } elseif (is_array($managerIdsArray)) {
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
            if (!empty($managerIds)) {
                $model->manager_id = $managerIds[0];
            }
            
            // Устанавливаем direction_id
            $model->direction_id = $direction->_id;
            $model->department_id = null;
            
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Проект успешно создан.');
                return $this->redirect(['/project/view', 'id' => (string)$model->_id]);
            }
        }
        
        return $this->render('create-project', [
            'model' => $model,
            'direction' => $direction,
        ]);
    }

    /**
     * Search managers for direction
     * @return array JSON response with search results
     */
    public function actionSearchManagers()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $query = trim(Yii::$app->request->get('q', ''));
        $limit = 20;
        
        if (empty($query) || strlen($query) < 2) {
            return ['results' => []];
        }
        
        try {
            $escapedQuery = preg_quote($query, '/');
            $regex = new \MongoDB\BSON\Regex($escapedQuery, 'i');
            
            // Поиск по ФИО или email
            $users = User::find()
                ->where([
                    '$or' => [
                        ['fio' => $regex],
                        ['email' => $regex]
                    ]
                ])
                ->orderBy(['fio' => SORT_ASC])
                ->limit($limit)
                ->all();
            
            $results = [];
            $roleLabels = [
                User::ROLE_ADMIN => 'Админ',
                User::ROLE_RECTOR => 'Ректор',
                User::ROLE_HEAD => 'Руководитель',
                User::ROLE_TOP_MANAGER => 'Топ-менеджер',
                User::ROLE_MANAGER => 'Менеджер',
                User::ROLE_EXECUTOR => 'Исполнитель',
            ];
            foreach ($users as $user) {
                if ($user->fio && $user->email) {
                    $roleLabel = $roleLabels[$user->role] ?? $user->role;
                    
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
                'error' => YII_DEBUG ? $e->getMessage() : 'Ошибка при поиске'
            ];
        }
    }

    /**
     * Mark legacy projects as legacy
     * @return mixed
     */
    public function actionMarkLegacy()
    {
        $user = Yii::$app->user->identity;
        $userGlobalRole = GlobalProjectRole::getUserRole($user->_id);
        
        if (!$this->canManageDirections($user, $userGlobalRole)) {
            throw new ForbiddenHttpException('У вас нет прав для этого действия.');
        }
        
        $projectId = Yii::$app->request->post('project_id');
        if ($projectId) {
            $project = Project::findOne(['_id' => new \MongoDB\BSON\ObjectId($projectId)]);
            if ($project && $project->isGlobal() && empty($project->direction_id)) {
                $project->is_legacy = true;
                $project->save(false);
                Yii::$app->session->setFlash('success', 'Проект помечен как устаревший и скрыт.');
            }
        }
        
        return $this->redirect(['index']);
    }

    /**
     * Check if user can manage directions
     * @param User $user
     * @param string|null $userGlobalRole
     * @return bool
     */
    /**
     * Может управлять направлениями: только админ и глобальный руководитель
     */
    protected function canManageDirections($user, $userGlobalRole)
    {
        return $user->role === User::ROLE_ADMIN || 
               $userGlobalRole === GlobalProjectRole::ROLE_RECTOR;
    }

    /**
     * Может создавать/редактировать проекты в направлении: админ, глоб. руководитель, глоб. топ-менеджер
     */
    protected function canManageProjects($user, $userGlobalRole)
    {
        return $user->role === User::ROLE_ADMIN || 
               $userGlobalRole === GlobalProjectRole::ROLE_RECTOR || 
               $userGlobalRole === GlobalProjectRole::ROLE_GLOBAL_TOP_MANAGER;
    }

    /**
     * Process manager_ids from form
     * @param Direction $model
     */
    protected function processManagerIds($model)
    {
        $managerIds = [];
        if (isset($_POST['Direction']['manager_ids_array'])) {
            $managerIdsArray = $_POST['Direction']['manager_ids_array'];
            
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
            } elseif (is_array($managerIdsArray)) {
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
    }

    /**
     * Prepare manager_ids_array for form display
     * @param Direction $model
     */
    protected function prepareManagerIdsForForm($model)
    {
        $model->manager_ids_array = [];
        if (!empty($model->manager_ids)) {
            foreach ($model->manager_ids as $id) {
                if ($id instanceof \MongoDB\BSON\ObjectId) {
                    $model->manager_ids_array[] = (string)$id;
                } elseif (is_string($id)) {
                    $model->manager_ids_array[] = $id;
                }
            }
        }
    }

    /**
     * Finds the Direction model based on its primary key value.
     * @param string $id
     * @return Direction the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Direction::findOne(['_id' => new \MongoDB\BSON\ObjectId($id)])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('Направление не найдено.');
    }
}
