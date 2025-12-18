<?php

namespace app\controllers;

use Yii;
use app\models\ProjectSpec;
use app\models\Project;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use app\models\User;

/**
 * ProjectSpecController implements the CRUD actions for ProjectSpec model.
 */
class ProjectSpecController extends Controller
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
     * Displays a single ProjectSpec model.
     * @param string $project_id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($project_id)
    {
        // Конвертируем project_id в ObjectId если это строка
        $projectIdObj = is_string($project_id) ? new \MongoDB\BSON\ObjectId($project_id) : $project_id;
        
        $project = Project::findOne(['_id' => $projectIdObj]);
        if (!$project) {
            throw new NotFoundHttpException('Проект не найден.');
        }
        
        $this->checkProjectAccess($project);
        
        $model = ProjectSpec::findOne(['project_id' => $projectIdObj]);
        if (!$model) {
            throw new NotFoundHttpException('Техническое задание не найдено.');
        }

        return $this->render('view', [
            'model' => $model,
            'project' => $project,
        ]);
    }

    /**
     * Creates a new ProjectSpec model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @param string $project_id
     * @return mixed
     */
    public function actionCreate($project_id)
    {
        // Конвертируем project_id в ObjectId если это строка
        $projectIdObj = is_string($project_id) ? new \MongoDB\BSON\ObjectId($project_id) : $project_id;
        
        $project = Project::findOne(['_id' => $projectIdObj]);
        if (!$project) {
            throw new NotFoundHttpException('Проект не найден.');
        }
        
        $user = Yii::$app->user->identity;
        
        // Админ, ректор (создатель проекта) и топ-менеджер могут создавать ТЗ
        if ($user->role !== User::ROLE_ADMIN && 
            $user->role !== User::ROLE_TOP_MANAGER &&
            ($user->role !== User::ROLE_RECTOR || (string)$project->manager_id !== (string)$user->_id)) {
            Yii::$app->session->setFlash('error', 'Вы не можете создавать ТЗ для этого проекта.');
            return $this->redirect(['project/view', 'id' => (string)$projectIdObj]);
        }
        
        // Топ-менеджер имеет доступ только к проектам своего подразделения
        if ($user->role === User::ROLE_TOP_MANAGER) {
            if (!$project->department_id || !$user->department_id || 
                (string)$project->department_id !== (string)$user->department_id) {
                Yii::$app->session->setFlash('error', 'Вы не можете создавать ТЗ для проектов других подразделений.');
                return $this->redirect(['project/view', 'id' => (string)$projectIdObj]);
            }
        }
        
        // Проверяем, не существует ли уже ТЗ
        $existingSpec = ProjectSpec::findOne(['project_id' => $projectIdObj]);
        if ($existingSpec) {
            Yii::$app->session->setFlash('info', 'ТЗ для этого проекта уже существует. Используйте редактирование.');
            return $this->redirect(['update', 'project_id' => (string)$projectIdObj]);
        }
        
        $model = new ProjectSpec();
        $model->project_id = $projectIdObj;
        $model->milestones = [];
        $model->metrics = [];
        $model->report_template = [];

        if ($model->load(Yii::$app->request->post())) {
            // Обработка milestones из формы
            if (isset($_POST['milestones']) && is_array($_POST['milestones'])) {
                $milestones = [];
                foreach ($_POST['milestones'] as $milestone) {
                    if (!empty($milestone['name'])) {
                        $milestones[] = [
                            'name' => $milestone['name'],
                            'deadline' => $milestone['deadline'] ?? null,
                            'done' => isset($milestone['done']) ? (bool)$milestone['done'] : false,
                        ];
                    }
                }
                $model->milestones = $milestones;
            }
            
            // Обработка metrics из формы
            if (isset($_POST['metrics']) && is_array($_POST['metrics'])) {
                $metrics = array_filter($_POST['metrics'], function($metric) {
                    return !empty(trim($metric));
                });
                $model->metrics = array_values($metrics);
            }
            
            // Обработка report_template из формы
            if (isset($_POST['report_template']) && is_array($_POST['report_template'])) {
                $template = [];
                foreach ($_POST['report_template'] as $item) {
                    if (isset($item['key']) && !empty(trim($item['key']))) {
                        $template[$item['key']] = $item['value'] ?? '';
                    }
                }
                $model->report_template = $template;
            }
            
            if ($model->save()) {
                // Рассчитываем следующий дедлайн
                $this->calculateNextDeadline($model);
                
                Yii::$app->session->setFlash('success', 'Техническое задание успешно создано.');
                return $this->redirect(['project/view', 'id' => (string)$project_id]);
            }
        }

        return $this->render('create', [
            'model' => $model,
            'project' => $project,
        ]);
    }

    /**
     * Updates an existing ProjectSpec model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param string $project_id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($project_id)
    {
        // Конвертируем project_id в ObjectId если это строка
        $projectIdObj = is_string($project_id) ? new \MongoDB\BSON\ObjectId($project_id) : $project_id;
        
        $project = Project::findOne(['_id' => $projectIdObj]);
        if (!$project) {
            throw new NotFoundHttpException('Проект не найден.');
        }
        
        $user = Yii::$app->user->identity;
        
        // ТЗ можно редактировать только если проект в статусе draft
        if ($project->status !== Project::STATUS_DRAFT) {
            Yii::$app->session->setFlash('error', 'ТЗ можно редактировать только для проектов в статусе "Черновик".');
            return $this->redirect(['project/view', 'id' => $project_id]);
        }
        
        // Админ, ректор (создатель проекта) и топ-менеджер могут редактировать ТЗ
        if ($user->role !== User::ROLE_ADMIN && 
            $user->role !== User::ROLE_TOP_MANAGER &&
            ($user->role !== User::ROLE_RECTOR || (string)$project->manager_id !== (string)$user->_id)) {
            Yii::$app->session->setFlash('error', 'Вы не можете редактировать ТЗ для этого проекта.');
            return $this->redirect(['project/view', 'id' => $project_id]);
        }
        
        // Топ-менеджер имеет доступ только к проектам своего подразделения
        if ($user->role === User::ROLE_TOP_MANAGER) {
            if (!$project->department_id || !$user->department_id || 
                (string)$project->department_id !== (string)$user->department_id) {
                Yii::$app->session->setFlash('error', 'Вы не можете редактировать ТЗ для проектов других подразделений.');
                return $this->redirect(['project/view', 'id' => $project_id]);
            }
        }
        
        $model = ProjectSpec::findOne(['project_id' => $project_id]);
        if (!$model) {
            throw new NotFoundHttpException('Техническое задание не найдено.');
        }

        if ($model->load(Yii::$app->request->post())) {
            // Обработка milestones из формы
            if (isset($_POST['milestones']) && is_array($_POST['milestones'])) {
                $milestones = [];
                foreach ($_POST['milestones'] as $milestone) {
                    if (!empty($milestone['name'])) {
                        $milestones[] = [
                            'name' => $milestone['name'],
                            'deadline' => $milestone['deadline'] ?? null,
                            'done' => isset($milestone['done']) ? (bool)$milestone['done'] : false,
                        ];
                    }
                }
                $model->milestones = $milestones;
            }
            
            // Обработка metrics из формы
            if (isset($_POST['metrics']) && is_array($_POST['metrics'])) {
                $metrics = array_filter($_POST['metrics'], function($metric) {
                    return !empty(trim($metric));
                });
                $model->metrics = array_values($metrics);
            }
            
            // Обработка report_template из формы
            if (isset($_POST['report_template']) && is_array($_POST['report_template'])) {
                $template = [];
                foreach ($_POST['report_template'] as $item) {
                    if (isset($item['key']) && !empty(trim($item['key']))) {
                        $template[$item['key']] = $item['value'] ?? '';
                    }
                }
                $model->report_template = $template;
            }
            
            if ($model->save()) {
                // Рассчитываем следующий дедлайн
                $this->calculateNextDeadline($model);
                
                Yii::$app->session->setFlash('success', 'Техническое задание успешно обновлено.');
                return $this->redirect(['project/view', 'id' => (string)$projectIdObj]);
            }
        }

        return $this->render('update', [
            'model' => $model,
            'project' => $project,
        ]);
    }

    /**
     * Рассчитывает следующий дедлайн отчета на основе периода отчетности
     * @param ProjectSpec $model
     */
    protected function calculateNextDeadline($model)
    {
        $project = Project::findOne(['_id' => $model->project_id]);
        if (!$project) {
            return;
        }
        
        $now = time();
        $daysToAdd = 0;
        
        switch ($model->report_period) {
            case ProjectSpec::PERIOD_DAILY:
                $daysToAdd = 1;
                break;
            case ProjectSpec::PERIOD_WEEKLY:
                $daysToAdd = 7;
                break;
            case ProjectSpec::PERIOD_BIWEEKLY:
                $daysToAdd = 14;
                break;
            case ProjectSpec::PERIOD_MONTHLY:
                $daysToAdd = 30;
                break;
            case ProjectSpec::PERIOD_CUSTOM:
                $daysToAdd = $model->custom_period_days ?? 7;
                break;
        }
        
        $nextDeadline = $now + ($daysToAdd * 24 * 60 * 60);
        $project->next_report_deadline = new \MongoDB\BSON\UTCDateTime($nextDeadline * 1000);
        $project->save(false);
    }

    /**
     * Проверка доступа к проекту
     * @param Project $project
     * @throws NotFoundHttpException
     */
    protected function checkProjectAccess($project)
    {
        $user = Yii::$app->user->identity;
        
        // Админ имеет полный доступ
        if ($user->role === User::ROLE_ADMIN) {
            return;
        }
        
        // Ректор имеет доступ к проектам своего подразделения
        if ($user->role === User::ROLE_RECTOR) {
            if ($project->department_id && $user->department_id && 
                (string)$project->department_id === (string)$user->department_id) {
                return;
            }
        }
        
        // Топ-менеджер и менеджер имеют доступ к проектам своего подразделения
        if ($user->role === User::ROLE_TOP_MANAGER || $user->role === User::ROLE_MANAGER) {
            if ($project->department_id && $user->department_id && 
                (string)$project->department_id === (string)$user->department_id) {
                return;
            }
        }
        
        // Исполнитель имеет доступ к проектам своего подразделения
        if ($user->role === User::ROLE_EXECUTOR) {
            if ($project->department_id && $user->department_id && 
                (string)$project->department_id === (string)$user->department_id) {
                return;
            }
        }
        
        throw new NotFoundHttpException('У вас нет доступа к этому проекту.');
    }
}

