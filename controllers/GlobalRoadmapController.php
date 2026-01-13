<?php

namespace app\controllers;

use Yii;
use app\models\Roadmap;
use app\models\RoadmapStage;
use app\models\RoadmapStageGoal;
use app\models\GlobalProjectRole;
use app\models\User;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\web\Response;

/**
 * GlobalRoadmapController implements the CRUD actions for Global Roadmap model.
 */
class GlobalRoadmapController extends Controller
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
                    'delete-stage' => ['POST'],
                    'delete-goal' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Displays the global roadmap.
     * @return mixed
     */
    public function actionIndex()
    {
        // Получаем глобальную дорожную карту (department_id = null)
        $roadmap = Roadmap::findOne(['department_id' => null]);
        
        if (!$roadmap) {
            // Если глобальной дорожной карты нет, создаем ее
            $user = Yii::$app->user->identity;
            
            // Только админ или ректор в глобальном проекте может создать глобальную дорожную карту
            $userGlobalRole = GlobalProjectRole::getUserRole($user->_id);
            if ($user->role !== User::ROLE_ADMIN && $userGlobalRole !== GlobalProjectRole::ROLE_RECTOR) {
                Yii::$app->session->setFlash('error', 'У вас нет прав для создания глобальной дорожной карты.');
                return $this->redirect(['/global-project/index']);
            }
            
            $roadmap = new Roadmap();
            $roadmap->department_id = null; // Глобальная дорожная карта не привязана к департаменту
            
            if ($roadmap->save()) {
                Yii::$app->session->setFlash('success', 'Глобальная дорожная карта успешно создана.');
            } else {
                Yii::$app->session->setFlash('error', 'Ошибка при создании глобальной дорожной карты.');
            }
        }
        
        return $this->redirect(['view']);
    }

    /**
     * Displays a single Global Roadmap model.
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView()
    {
        $roadmap = Roadmap::findOne(['department_id' => null]);
        
        if (!$roadmap) {
            Yii::$app->session->setFlash('info', 'Глобальная дорожная карта еще не создана.');
        }
        
        return $this->render('view', [
            'roadmap' => $roadmap,
        ]);
    }

    /**
     * Updates an existing Global Roadmap's stages.
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate()
    {
        $roadmap = Roadmap::findOne(['department_id' => null]);
        if (!$roadmap) {
            Yii::$app->session->setFlash('error', 'Глобальная дорожная карта не найдена.');
            return $this->redirect(['view']);
        }
        
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

        $roadmap = Roadmap::findOne(['_id' => new \MongoDB\BSON\ObjectId($roadmapId)]);
        if (!$roadmap || !$roadmap->isGlobal()) {
            return ['success' => false, 'message' => 'Глобальная дорожная карта не найдена.'];
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
            
            // Проверяем, что этап принадлежит глобальной дорожной карте
            $roadmap = $stage->roadmap;
            if (!$roadmap || !$roadmap->isGlobal()) {
                return ['success' => false, 'message' => 'Этап не принадлежит глобальной дорожной карте.'];
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
     * @return mixed
     */
    public function actionDeleteStage($id)
    {
        $stage = RoadmapStage::findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
        if (!$stage) {
            throw new NotFoundHttpException('Этап не найден.');
        }
        
        // Проверяем, что этап принадлежит глобальной дорожной карте
        $roadmap = $stage->roadmap;
        if (!$roadmap || !$roadmap->isGlobal()) {
            throw new NotFoundHttpException('Этап не принадлежит глобальной дорожной карте.');
        }

        // Удаляем все цели этапа
        RoadmapStageGoal::deleteAll(['stage_id' => $stage->_id]);
        
        $stage->delete();
        Yii::$app->session->setFlash('success', 'Этап успешно удален.');

        return $this->redirect(['update']);
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
        
        // Проверяем, что этап принадлежит глобальной дорожной карте
        $roadmap = $stage->roadmap;
        if (!$roadmap || !$roadmap->isGlobal()) {
            return ['success' => false, 'message' => 'Этап не принадлежит глобальной дорожной карте.'];
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
     * @return mixed
     */
    public function actionDeleteGoal($id)
    {
        $goal = RoadmapStageGoal::findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
        if (!$goal) {
            throw new NotFoundHttpException('Цель не найдена.');
        }
        
        // Проверяем, что цель принадлежит этапу глобальной дорожной карты
        $stage = $goal->stage;
        if ($stage) {
            $roadmap = $stage->roadmap;
            if (!$roadmap || !$roadmap->isGlobal()) {
                throw new NotFoundHttpException('Цель не принадлежит глобальной дорожной карте.');
            }
        }

        $goal->delete();
        Yii::$app->session->setFlash('success', 'Цель успешно удалена.');

        return $this->redirect(['update']);
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
        if (!$roadmap || !$roadmap->isGlobal()) {
            return ['success' => false, 'message' => 'Глобальная дорожная карта не найдена.'];
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

