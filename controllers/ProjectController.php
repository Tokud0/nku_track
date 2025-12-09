<?php

namespace app\controllers;

use Yii;
use app\models\Project;
use app\models\ProjectSearch;
use app\models\ProjectSpec;
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
        
        if ($user->role === User::ROLE_MANAGER || $user->role === User::ROLE_RECTOR || $user->role === User::ROLE_ADMIN) {
            // Ректор и админ видят все проекты
            if ($user->role === User::ROLE_RECTOR || $user->role === User::ROLE_ADMIN) {
                $managerId = null;
            } else {
                // Менеджер видит проекты своего подразделения
                if ($user->department_id) {
                    $departmentId = $user->department_id;
                }
            }
        } elseif ($user->role === User::ROLE_EXECUTOR) {
            // Исполнитель видит только проекты своего подразделения, где он назначен
            $executorId = $user->_id;
            if ($user->department_id) {
                $departmentId = $user->department_id;
            }
        }
        
        // Если в запросе указано подразделение, используем его
        if (isset(Yii::$app->request->queryParams['ProjectSearch']['department_id']) && 
            !empty(Yii::$app->request->queryParams['ProjectSearch']['department_id'])) {
            $departmentId = Yii::$app->request->queryParams['ProjectSearch']['department_id'];
        }
        
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, $managerId, $executorId, $departmentId);

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
        
        // Только менеджер или админ может создавать проект
        if ($user->role !== User::ROLE_MANAGER && $user->role !== User::ROLE_ADMIN) {
            Yii::$app->session->setFlash('error', 'Только руководитель может создавать проекты.');
            return $this->redirect(['index']);
        }
        
        $model = new Project();
        $model->manager_id = $user->_id;
        $model->status = Project::STATUS_DRAFT;
        $model->executors = [];
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
            // Только админ или ректор могут устанавливать подразделение
            if (($user->role === User::ROLE_ADMIN || $user->role === User::ROLE_RECTOR) && !empty($_POST['Project']['department_id'])) {
                $model->department_id = new \MongoDB\BSON\ObjectId($_POST['Project']['department_id']);
            } elseif ($user->role === User::ROLE_MANAGER) {
                // Менеджер не может изменять подразделение, оставляем текущее или null
                if ($model->isNewRecord) {
                    $model->department_id = null;
                }
                // При редактировании оставляем существующее значение
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
        
        // Только менеджер проекта или админ может редактировать
        if (($user->role !== User::ROLE_MANAGER && $user->role !== User::ROLE_ADMIN) || 
            ($user->role === User::ROLE_MANAGER && (string)$model->manager_id !== (string)$user->_id)) {
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
            // Только админ или ректор могут устанавливать подразделение
            if (($user->role === User::ROLE_ADMIN || $user->role === User::ROLE_RECTOR) && !empty($_POST['Project']['department_id'])) {
                $model->department_id = new \MongoDB\BSON\ObjectId($_POST['Project']['department_id']);
            } elseif ($user->role === User::ROLE_MANAGER) {
                // Менеджер не может изменять подразделение, оставляем текущее или null
                if ($model->isNewRecord) {
                    $model->department_id = null;
                }
                // При редактировании оставляем существующее значение
            }
            
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
        
        // Только менеджер проекта или админ может удалить
        if (($user->role !== User::ROLE_MANAGER && $user->role !== User::ROLE_ADMIN) || 
            ($user->role === User::ROLE_MANAGER && (string)$model->manager_id !== (string)$user->_id)) {
            Yii::$app->session->setFlash('error', 'Вы не можете удалить этот проект.');
            return $this->redirect(['view', 'id' => $id]);
        }
        
        $model->delete();
        Yii::$app->session->setFlash('success', 'Проект успешно удален.');

        return $this->redirect(['index']);
    }

    /**
     * Назначение исполнителей проекту
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionAssignExecutors($id)
    {
        $model = $this->findModel($id);
        $user = Yii::$app->user->identity;
        
        // Только менеджер проекта или админ может назначать исполнителей
        if (($user->role !== User::ROLE_MANAGER && $user->role !== User::ROLE_ADMIN) || 
            ($user->role === User::ROLE_MANAGER && (string)$model->manager_id !== (string)$user->_id)) {
            Yii::$app->session->setFlash('error', 'Вы не можете назначать исполнителей для этого проекта.');
            return $this->redirect(['view', 'id' => $id]);
        }
        
        // Получаем исполнителей только из подразделения проекта
        $executorsQuery = User::find()->where(['role' => User::ROLE_EXECUTOR]);
        
        // Если у проекта есть подразделение, фильтруем по нему
        if ($model->department_id) {
            $executorsQuery->andWhere(['department_id' => $model->department_id]);
        }
        
        $executors = $executorsQuery->all();
        
        if (Yii::$app->request->isPost) {
            $selectedExecutors = Yii::$app->request->post('executors', []);
            
            // Конвертируем строки в ObjectId
            $executorIds = [];
            foreach ($selectedExecutors as $executorId) {
                try {
                    $executorIds[] = new \MongoDB\BSON\ObjectId($executorId);
                } catch (\Exception $e) {
                    // Пропускаем невалидные ID
                }
            }
            
            $model->executors = $executorIds;
            
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Исполнители успешно назначены.');
                return $this->redirect(['view', 'id' => (string)$model->_id]);
            }
        }
        
        return $this->render('assign-executors', [
            'model' => $model,
            'executors' => $executors,
        ]);
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
        
        // Админ, ректор и менеджер проекта имеют доступ
        if ($user->role === User::ROLE_ADMIN || 
            $user->role === User::ROLE_RECTOR || 
            ($user->role === User::ROLE_MANAGER && (string)$model->manager_id === (string)$user->_id)) {
            return;
        }
        
        // Исполнитель имеет доступ только если он назначен на проект
        if ($user->role === User::ROLE_EXECUTOR) {
            $isExecutor = false;
            foreach ($model->executors ?: [] as $executorId) {
                if ((string)$executorId === (string)$user->_id) {
                    $isExecutor = true;
                    break;
                }
            }
            if ($isExecutor) {
                return;
            }
        }
        
        throw new NotFoundHttpException('У вас нет доступа к этому проекту.');
    }
}

