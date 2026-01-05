<?php

namespace app\modules\admin\controllers;

use Yii;
use app\models\Roadmap;
use app\models\RoadmapStage;
use app\models\RoadmapStageGoal;
use app\models\Department;
use app\models\User;
use app\modules\admin\models\RoadmapSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\web\Response;

/**
 * RoadmapController implements the CRUD actions for Roadmap model in admin panel.
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
                            return Yii::$app->user->identity->role === User::ROLE_ADMIN;
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
     * Lists all Roadmaps for all departments.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new RoadmapSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        // Получаем дорожные карты для каждого подразделения
        $roadmaps = [];
        foreach ($dataProvider->getModels() as $department) {
            $roadmap = Roadmap::findOne(['department_id' => $department->_id]);
            $roadmaps[(string)$department->_id] = $roadmap;
        }

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'roadmaps' => $roadmaps,
        ]);
    }

    /**
     * Displays a single Roadmap model.
     * @param string $id Department ID
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        $department = Department::findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
        if (!$department) {
            throw new NotFoundHttpException('Подразделение не найдено.');
        }

        $roadmap = Roadmap::findOne(['department_id' => $department->_id]);
        
        if (!$roadmap) {
            Yii::$app->session->setFlash('info', 'Для этого подразделения еще не создана дорожная карта.');
        }

        return $this->render('view', [
            'department' => $department,
            'roadmap' => $roadmap,
        ]);
    }

    /**
     * Creates a new Roadmap for a department.
     * @param string $id Department ID
     * @return mixed
     */
    public function actionCreate($id)
    {
        $department = Department::findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
        if (!$department) {
            throw new NotFoundHttpException('Подразделение не найдено.');
        }

        // Проверяем, нет ли уже дорожной карты
        $existingRoadmap = Roadmap::findOne(['department_id' => $department->_id]);
        if ($existingRoadmap) {
            Yii::$app->session->setFlash('info', 'Дорожная карта для этого подразделения уже существует.');
            return $this->redirect(['view', 'id' => $id]);
        }

        $model = new Roadmap();
        $model->department_id = $department->_id;

        if ($model->save()) {
            Yii::$app->session->setFlash('success', 'Дорожная карта успешно создана.');
            return $this->redirect(['view', 'id' => $id]);
        } else {
            Yii::$app->session->setFlash('error', 'Ошибка при создании дорожной карты.');
            return $this->redirect(['view', 'id' => $id]);
        }
    }

    /**
     * Updates an existing Roadmap's stages.
     * @param string $id Department ID
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $department = Department::findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
        if (!$department) {
            throw new NotFoundHttpException('Подразделение не найдено.');
        }

        $roadmap = Roadmap::findOne(['department_id' => $department->_id]);
        if (!$roadmap) {
            Yii::$app->session->setFlash('error', 'Дорожная карта не найдена.');
            return $this->redirect(['view', 'id' => $id]);
        }

        $stages = $roadmap->stages;

        return $this->render('update', [
            'department' => $department,
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

        $roadmap = Roadmap::findOne(['_id' => new \MongoDB\BSON\ObjectId($roadmapId)]);
        if (!$roadmap) {
            return ['success' => false, 'message' => 'Дорожная карта не найдена.'];
        }

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
            
            $stage = RoadmapStage::findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
            if (!$stage) {
                return ['success' => false, 'message' => 'Этап не найден.'];
            }

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
     * @param string|null $department_id Department ID for redirect (from POST or URL)
     * @return mixed
     */
    public function actionDeleteStage($id, $department_id = null)
    {
        $stage = RoadmapStage::findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
        if (!$stage) {
            throw new NotFoundHttpException('Этап не найден.');
        }

        // Получаем department_id из POST, если не передан в URL
        if (!$department_id) {
            $department_id = Yii::$app->request->post('department_id');
        }

        if (!$department_id) {
            // Пытаемся получить из roadmap
            $roadmap = $stage->roadmap;
            if ($roadmap) {
                $department_id = (string)$roadmap->department_id;
            }
        }

        // Удаляем все цели этапа
        RoadmapStageGoal::deleteAll(['stage_id' => $stage->_id]);
        
        $stage->delete();
        Yii::$app->session->setFlash('success', 'Этап успешно удален.');

        if ($department_id) {
            return $this->redirect(['update', 'id' => $department_id]);
        }
        
        return $this->redirect(['index']);
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
     * @param string|null $department_id Department ID for redirect (from POST or URL)
     * @return mixed
     */
    public function actionDeleteGoal($id, $department_id = null)
    {
        $goal = RoadmapStageGoal::findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
        if (!$goal) {
            throw new NotFoundHttpException('Цель не найдена.');
        }

        // Получаем department_id из POST, если не передан в URL
        if (!$department_id) {
            $department_id = Yii::$app->request->post('department_id');
        }

        if (!$department_id) {
            // Пытаемся получить из stage -> roadmap
            $stage = $goal->stage;
            if ($stage) {
                $roadmap = $stage->roadmap;
                if ($roadmap) {
                    $department_id = (string)$roadmap->department_id;
                }
            }
        }

        $goal->delete();
        Yii::$app->session->setFlash('success', 'Цель успешно удалена.');

        if ($department_id) {
            return $this->redirect(['update', 'id' => $department_id]);
        }
        
        return $this->redirect(['index']);
    }

    /**
     * Deletes a roadmap.
     * @param string $id Department ID
     * @return mixed
     */
    public function actionDelete($id)
    {
        $department = Department::findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
        if (!$department) {
            throw new NotFoundHttpException('Подразделение не найдено.');
        }

        $roadmap = Roadmap::findOne(['department_id' => $department->_id]);
        if ($roadmap) {
            // Удаляем все цели и этапы
            $stages = $roadmap->stages;
            foreach ($stages as $stage) {
                RoadmapStageGoal::deleteAll(['stage_id' => $stage->_id]);
            }
            RoadmapStage::deleteAll(['roadmap_id' => $roadmap->_id]);
            $roadmap->delete();
            Yii::$app->session->setFlash('success', 'Дорожная карта успешно удалена.');
        }

        return $this->redirect(['view', 'id' => $id]);
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
        
        $roadmap = Roadmap::findOne(['_id' => new \MongoDB\BSON\ObjectId($roadmapId)]);
        if (!$roadmap) {
            return ['success' => false, 'message' => 'Дорожная карта не найдена.'];
        }

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
}

