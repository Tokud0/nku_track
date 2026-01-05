<?php

namespace app\controllers;

use Yii;
use app\models\Roadmap;
use app\models\RoadmapStage;
use app\models\RoadmapStageGoal;
use app\models\Department;
use app\models\User;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\web\Response;

/**
 * RoadmapController implements the CRUD actions for Roadmap model.
 * Доступен для руководителя (rector) и топ-менеджера (top_manager).
 */
class RoadmapController extends Controller
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
                        'matchCallback' => function ($rule, $action) {
                            $user = Yii::$app->user->identity;
                            // Ректор может просматривать все дорожные карты
                            if ($user->role === User::ROLE_RECTOR) {
                                return true;
                            }
                            // Руководитель и топ-менеджер могут управлять дорожными картами
                            return in_array($user->role, [User::ROLE_HEAD, User::ROLE_TOP_MANAGER]);
                        },
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                    'delete-stage' => ['POST'],
                    'delete-goal' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Displays roadmaps for user's department.
     * @return mixed
     */
    public function actionIndex()
    {
        $user = Yii::$app->user->identity;
        
        if (!$user->department_id) {
            Yii::$app->session->setFlash('error', 'Вы не привязаны к подразделению.');
            return $this->redirect(['/site/index']);
        }

        $department = Department::findOne(['_id' => $user->department_id]);
        if (!$department) {
            throw new NotFoundHttpException('Подразделение не найдено.');
        }

        $roadmap = Roadmap::findOne(['department_id' => $user->department_id]);
        
        return $this->render('index', [
            'department' => $department,
            'roadmap' => $roadmap,
        ]);
    }

    /**
     * Creates a new Roadmap for user's department.
     * If creation is successful, the browser will be redirected to the 'index' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $user = Yii::$app->user->identity;
        
        // Ректор может только просматривать, не может создавать
        if ($user->role === User::ROLE_RECTOR) {
            Yii::$app->session->setFlash('error', 'Ректор может только просматривать дорожные карты.');
            return $this->redirect(['index']);
        }
        
        if (!$user->department_id) {
            Yii::$app->session->setFlash('error', 'Вы не привязаны к подразделению.');
            return $this->redirect(['/site/index']);
        }

        // Проверяем, нет ли уже дорожной карты
        $existingRoadmap = Roadmap::findOne(['department_id' => $user->department_id]);
        if ($existingRoadmap) {
            Yii::$app->session->setFlash('info', 'Дорожная карта для вашего подразделения уже существует.');
            return $this->redirect(['index']);
        }

        $model = new Roadmap();
        $model->department_id = $user->department_id;

        if ($model->save()) {
            Yii::$app->session->setFlash('success', 'Дорожная карта успешно создана.');
            return $this->redirect(['index']);
        }

        return $this->redirect(['index']);
    }

    /**
     * Updates an existing Roadmap's stages.
     * @param string $id Roadmap ID
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $user = Yii::$app->user->identity;
        
        // Ректор может только просматривать, не может редактировать
        if ($user->role === User::ROLE_RECTOR) {
            Yii::$app->session->setFlash('error', 'Ректор может только просматривать дорожные карты.');
            return $this->redirect(['index']);
        }
        
        $roadmap = $this->findRoadmap($id);
        $this->checkAccess($roadmap);

        $stages = $roadmap->stages;

        return $this->render('update', [
            'roadmap' => $roadmap,
            'stages' => $stages,
        ]);
    }

    /**
     * Creates or updates a stage via AJAX.
     * @return array
     */
    public function actionSaveStage()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $id = Yii::$app->request->post('id');
        $roadmapId = Yii::$app->request->post('roadmap_id');
        $name = Yii::$app->request->post('name');
        $startMonth = (int)Yii::$app->request->post('start_month');
        $endMonth = (int)Yii::$app->request->post('end_month');
        $startDate = Yii::$app->request->post('start_date', '');
        $endDate = Yii::$app->request->post('end_date', '');
        $description = Yii::$app->request->post('description', '');

        if (!$roadmapId || !$name || $startMonth === null || $endMonth === null) {
            return ['success' => false, 'message' => 'Не все обязательные поля заполнены.'];
        }

        $user = Yii::$app->user->identity;
        
        // Ректор может только просматривать, не может редактировать
        if ($user->role === User::ROLE_RECTOR) {
            return ['success' => false, 'message' => 'Ректор может только просматривать дорожные карты.'];
        }
        
        $roadmap = Roadmap::findOne(['_id' => new \MongoDB\BSON\ObjectId($roadmapId)]);
        if (!$roadmap) {
            return ['success' => false, 'message' => 'Дорожная карта не найдена.'];
        }

        $this->checkAccess($roadmap);

        if ($id) {
            $stage = RoadmapStage::findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
            if (!$stage || (string)$stage->roadmap_id !== (string)$roadmap->_id) {
                return ['success' => false, 'message' => 'Этап не найден.'];
            }
        } else {
            $stage = new RoadmapStage();
            $stage->roadmap_id = $roadmap->_id;
        }

        $stage->name = $name;
        $stage->start_month = $startMonth;
        $stage->end_month = $endMonth;
        $stage->description = $description;
        
        // Обработка дат
        if (!empty($startDate)) {
            $stage->start_date = new \MongoDB\BSON\UTCDateTime(strtotime($startDate) * 1000);
        } else {
            $stage->start_date = null;
        }
        if (!empty($endDate)) {
            $stage->end_date = new \MongoDB\BSON\UTCDateTime(strtotime($endDate) * 1000);
        } else {
            $stage->end_date = null;
        }

        if ($stage->save()) {
            return [
                'success' => true,
                'message' => 'Этап успешно сохранен.',
                'stage' => [
                    'id' => (string)$stage->_id,
                    'name' => $stage->name,
                    'start_month' => $stage->start_month,
                    'end_month' => $stage->end_month,
                    'start_date' => $stage->start_date instanceof \MongoDB\BSON\UTCDateTime ? date('Y-m-d', $stage->start_date->toDateTime()->getTimestamp()) : '',
                    'end_date' => $stage->end_date instanceof \MongoDB\BSON\UTCDateTime ? date('Y-m-d', $stage->end_date->toDateTime()->getTimestamp()) : '',
                    'description' => $stage->description,
                ]
            ];
        } else {
            return ['success' => false, 'message' => 'Ошибка при сохранении: ' . implode(', ', $stage->getFirstErrors())];
        }
    }

    /**
     * Completes a stage via AJAX.
     * @return array
     */
    public function actionCompleteStage()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        try {
            $id = Yii::$app->request->post('id');
            $completionFormat = Yii::$app->request->post('completion_format', '');

            if (!$id) {
                return ['success' => false, 'message' => 'ID этапа не указан.'];
            }

            if (empty(trim($completionFormat))) {
                return ['success' => false, 'message' => 'Необходимо указать формат завершения.'];
            }

            $user = Yii::$app->user->identity;
            
            // Ректор может только просматривать, не может редактировать
            if ($user->role === User::ROLE_RECTOR) {
                return ['success' => false, 'message' => 'Ректор может только просматривать дорожные карты.'];
            }
            
            $stage = RoadmapStage::findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
            if (!$stage) {
                return ['success' => false, 'message' => 'Этап не найден.'];
            }

            $roadmap = $stage->roadmap;
            $this->checkAccess($roadmap);

            // Устанавливаем завершение этапа
            $stage->is_completed = true;
            $stage->completion_format = trim($completionFormat);
            $stage->completed_at = new \MongoDB\BSON\UTCDateTime();

            if ($stage->save()) {
                return [
                    'success' => true,
                    'message' => 'Этап успешно завершен.',
                    'stage' => [
                        'id' => (string)$stage->_id,
                        'is_completed' => $stage->is_completed,
                        'completion_format' => $stage->completion_format,
                    ]
                ];
            } else {
                $errors = $stage->getFirstErrors();
                return ['success' => false, 'message' => 'Ошибка при сохранении: ' . (!empty($errors) ? implode(', ', $errors) : 'Неизвестная ошибка')];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Ошибка: ' . $e->getMessage()];
        }
    }

    /**
     * Deletes a stage.
     * @param string $id Stage ID
     * @return mixed
     */
    public function actionDeleteStage($id)
    {
        $user = Yii::$app->user->identity;
        
        // Ректор может только просматривать, не может удалять
        if ($user->role === User::ROLE_RECTOR) {
            return ['success' => false, 'message' => 'Ректор может только просматривать дорожные карты.'];
        }
        
        $stage = RoadmapStage::findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
        if (!$stage) {
            throw new NotFoundHttpException('Этап не найден.');
        }

        $roadmap = $stage->roadmap;
        $this->checkAccess($roadmap);

        // Удаляем все цели этапа
        RoadmapStageGoal::deleteAll(['stage_id' => $stage->_id]);
        
        $stage->delete();
        Yii::$app->session->setFlash('success', 'Этап успешно удален.');

        return $this->redirect(['update', 'id' => (string)$roadmap->_id]);
    }

    /**
     * Creates or updates a goal via AJAX.
     * @return array
     */
    public function actionSaveGoal()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $id = Yii::$app->request->post('id');
        $stageId = Yii::$app->request->post('stage_id');
        $title = Yii::$app->request->post('title');
        $description = Yii::$app->request->post('description', '');

        if (!$stageId || !$title) {
            return ['success' => false, 'message' => 'Не все обязательные поля заполнены.'];
        }

        $stage = RoadmapStage::findOne(['_id' => new \MongoDB\BSON\ObjectId($stageId)]);
        if (!$stage) {
            return ['success' => false, 'message' => 'Этап не найден.'];
        }

        $roadmap = $stage->roadmap;
        $this->checkAccess($roadmap);

        if ($id) {
            $goal = RoadmapStageGoal::findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
            if (!$goal || (string)$goal->stage_id !== (string)$stage->_id) {
                return ['success' => false, 'message' => 'Цель не найдена.'];
            }
        } else {
            $goal = new RoadmapStageGoal();
            $goal->stage_id = $stage->_id;
        }

        $goal->title = $title;
        $goal->description = $description;

        if ($goal->save()) {
            return [
                'success' => true,
                'message' => 'Цель успешно сохранена.',
                'goal' => [
                    'id' => (string)$goal->_id,
                    'title' => $goal->title,
                    'description' => $goal->description,
                ]
            ];
        } else {
            return ['success' => false, 'message' => 'Ошибка при сохранении: ' . implode(', ', $goal->getFirstErrors())];
        }
    }

    /**
     * Deletes a goal.
     * @param string $id Goal ID
     * @return mixed
     */
    public function actionDeleteGoal($id)
    {
        $user = Yii::$app->user->identity;
        
        // Ректор может только просматривать, не может удалять
        if ($user->role === User::ROLE_RECTOR) {
            Yii::$app->session->setFlash('error', 'Ректор может только просматривать дорожные карты.');
            return $this->redirect(['index']);
        }
        
        $goal = RoadmapStageGoal::findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
        if (!$goal) {
            throw new NotFoundHttpException('Цель не найдена.');
        }

        $stage = $goal->stage;
        $roadmap = $stage->roadmap;
        $this->checkAccess($roadmap);

        $goal->delete();
        Yii::$app->session->setFlash('success', 'Цель успешно удалена.');

        return $this->redirect(['update', 'id' => (string)$roadmap->_id]);
    }

    /**
     * Saves roadmap start date via AJAX.
     * @return array
     */
    public function actionSaveRoadmapDate()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $roadmapId = Yii::$app->request->post('roadmap_id');
        $startDate = Yii::$app->request->post('start_date', '');

        if (!$roadmapId) {
            return ['success' => false, 'message' => 'ID дорожной карты не указан.'];
        }

        $user = Yii::$app->user->identity;
        
        // Ректор может только просматривать, не может редактировать
        if ($user->role === User::ROLE_RECTOR) {
            return ['success' => false, 'message' => 'Ректор может только просматривать дорожные карты.'];
        }
        
        $roadmap = Roadmap::findOne(['_id' => new \MongoDB\BSON\ObjectId($roadmapId)]);
        if (!$roadmap) {
            return ['success' => false, 'message' => 'Дорожная карта не найдена.'];
        }

        $this->checkAccess($roadmap);

        // Обработка даты
        if (!empty($startDate)) {
            $roadmap->start_date = new \MongoDB\BSON\UTCDateTime(strtotime($startDate) * 1000);
        } else {
            $roadmap->start_date = null;
        }

        if ($roadmap->save()) {
            return [
                'success' => true,
                'message' => 'Дата начала дорожной карты успешно сохранена.',
            ];
        } else {
            return ['success' => false, 'message' => 'Ошибка при сохранении: ' . implode(', ', $roadmap->getFirstErrors())];
        }
    }

    /**
     * Finds the Roadmap model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return Roadmap the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findRoadmap($id)
    {
        if (($model = Roadmap::findOne(['_id' => new \MongoDB\BSON\ObjectId($id)])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('Дорожная карта не найдена.');
    }

    /**
     * Checks if user has access to this roadmap.
     * @param Roadmap $roadmap
     * @throws NotFoundHttpException
     */
    protected function checkAccess($roadmap)
    {
        $user = Yii::$app->user->identity;
        
        // Админ имеет полный доступ
        if ($user->role === User::ROLE_ADMIN) {
            return;
        }
        
        // Ректор может просматривать все дорожные карты (но не редактировать)
        if ($user->role === User::ROLE_RECTOR) {
            return;
        }
        
        // Руководитель и топ-менеджер имеют доступ к дорожным картам своего подразделения
        if (in_array($user->role, [User::ROLE_HEAD, User::ROLE_TOP_MANAGER])) {
            if ($roadmap->department_id && $user->department_id && 
                (string)$roadmap->department_id === (string)$user->department_id) {
                return;
            }
        }
        
        throw new NotFoundHttpException('У вас нет доступа к этой дорожной карте.');
    }
}

