<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\SignupForm;
use app\models\ContactForm;

class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        if (Yii::$app->user->isGuest) {
            return $this->render('index');
        }
        
        $user = Yii::$app->user->identity;
        $data = [];
        
        // Статистика для всех ролей
        $data['user'] = $user;
        
        // Для администратора и ректора показываем все проекты и задачи
        if ($user->role === \app\models\User::ROLE_ADMIN || $user->role === \app\models\User::ROLE_RECTOR) {
            $data['totalProjects'] = \app\models\Project::find()->count();
            $data['activeProjects'] = \app\models\Project::find()
                ->where(['status' => \app\models\Project::STATUS_ACTIVE])
                ->count();
            
            // Все задачи
            $allTasks = \app\models\Task::find()->all();
            $data['totalTasks'] = count($allTasks);
            
            // Сортируем по дате создания
            usort($allTasks, function($a, $b) {
                $aTime = $a->created_at instanceof \MongoDB\BSON\UTCDateTime ? $a->created_at->toDateTime()->getTimestamp() : 0;
                $bTime = $b->created_at instanceof \MongoDB\BSON\UTCDateTime ? $b->created_at->toDateTime()->getTimestamp() : 0;
                return $bTime - $aTime;
            });
            $data['recentTasks'] = array_slice($allTasks, 0, 5);
            
            // Последние проекты
            $data['recentProjects'] = \app\models\Project::find()
                ->orderBy(['created_at' => SORT_DESC])
                ->limit(5)
                ->all();
        }
        // Для всех остальных авторизованных пользователей показываем проекты их подразделения
        elseif ($user->department_id) {
            $projectsQuery = \app\models\Project::find()
                ->where(['department_id' => $user->department_id]);
            
            $data['totalProjects'] = $projectsQuery->count();
            $data['activeProjects'] = \app\models\Project::find()
                ->where(['department_id' => $user->department_id, 'status' => \app\models\Project::STATUS_ACTIVE])
                ->count();
            
            // Получаем ID проектов подразделения
            $projectIds = \app\models\Project::find()
                ->select(['_id'])
                ->where(['department_id' => $user->department_id])
                ->column();
            
            // Конвертируем в ObjectId если нужно
            $projectIdsObj = [];
            foreach ($projectIds as $id) {
                if (is_string($id)) {
                    try {
                        $projectIdsObj[] = new \MongoDB\BSON\ObjectId($id);
                    } catch (\Exception $e) {
                        // Пропускаем невалидные ID
                    }
                } else {
                    $projectIdsObj[] = $id;
                }
            }
            
            // Задачи пользователя
            $allTasks = [];
            if (!empty($projectIdsObj)) {
                $allTasks = \app\models\Task::find()
                    ->where(['project_id' => ['$in' => $projectIdsObj]])
                    ->all();
            }
            
            // Для исполнителя показываем только его задачи (в т.ч. прикреплённые к задачам других подразделений)
            if ($user->role === \app\models\User::ROLE_EXECUTOR) {
                $userTasks = [];
                foreach ($allTasks as $task) {
                    if ($task->isAssignedToUser($user)) {
                        $userTasks[] = $task;
                    }
                }
                // Добавляем задачи из проектов других подразделений, где пользователь прикреплён
                $userTaskIds = array_map(function ($t) { return (string)$t->_id; }, $userTasks);
                $extraTasks = \app\models\Task::find()
                    ->where([
                        '$or' => [
                            ['executor_user_ids' => $user->_id],
                            ['executor_user_from_department_id' => $user->_id],
                            ['executor_user_from_subdepartment_id' => $user->_id],
                        ],
                    ])
                    ->andWhere(['project_id' => ['$nin' => $projectIdsObj]])
                    ->all();
                $approvedReqs = \app\models\TaskExecutorRequest::find()
                    ->where(['user_id' => $user->_id, 'status' => \app\models\TaskExecutorRequest::STATUS_APPROVED])
                    ->all();
                $extraTaskIds = array_map(function ($r) { return $r->task_id; }, $approvedReqs);
                if (!empty($extraTaskIds)) {
                    $fromReqs = \app\models\Task::find()
                        ->where(['_id' => ['$in' => $extraTaskIds]])
                        ->andWhere(['project_id' => ['$nin' => $projectIdsObj]])
                        ->all();
                    $extraTasks = array_merge($extraTasks, $fromReqs);
                }
                foreach ($extraTasks as $task) {
                    if (!in_array((string)$task->_id, $userTaskIds, true)) {
                        $userTasks[] = $task;
                        $userTaskIds[] = (string)$task->_id;
                    }
                }
                $data['totalTasks'] = count($userTasks);
                usort($userTasks, function($a, $b) {
                    $aTime = $a->created_at instanceof \MongoDB\BSON\UTCDateTime ? $a->created_at->toDateTime()->getTimestamp() : 0;
                    $bTime = $b->created_at instanceof \MongoDB\BSON\UTCDateTime ? $b->created_at->toDateTime()->getTimestamp() : 0;
                    return $bTime - $aTime;
                });
                $data['myTasks'] = array_slice($userTasks, 0, 5);
            } else {
                // Для не-исполнителя: последние задачи подразделения + задачи, где пользователь прикреплён (из других подразделений)
                $recentTasksList = $allTasks;
                $extraTasks = \app\models\Task::find()
                    ->where([
                        '$or' => [
                            ['executor_user_ids' => $user->_id],
                            ['executor_user_from_department_id' => $user->_id],
                            ['executor_user_from_subdepartment_id' => $user->_id],
                        ],
                    ])
                    ->andWhere(['project_id' => ['$nin' => $projectIdsObj]])
                    ->all();
                $approvedReqs = \app\models\TaskExecutorRequest::find()
                    ->where(['user_id' => $user->_id, 'status' => \app\models\TaskExecutorRequest::STATUS_APPROVED])
                    ->all();
                $extraIds = array_map(function ($r) { return $r->task_id; }, $approvedReqs);
                if (!empty($extraIds)) {
                    $fromReqs = \app\models\Task::find()
                        ->where(['_id' => ['$in' => $extraIds]])
                        ->andWhere(['project_id' => ['$nin' => $projectIdsObj]])
                        ->all();
                    $recentTasksList = array_merge($recentTasksList, $fromReqs);
                }
                $recentTasksList = array_merge($recentTasksList, $extraTasks);
                $recentTasksList = array_unique($recentTasksList, SORT_REGULAR);
                $data['totalTasks'] = count($recentTasksList);
                usort($recentTasksList, function($a, $b) {
                    $aTime = $a->created_at instanceof \MongoDB\BSON\UTCDateTime ? $a->created_at->toDateTime()->getTimestamp() : 0;
                    $bTime = $b->created_at instanceof \MongoDB\BSON\UTCDateTime ? $b->created_at->toDateTime()->getTimestamp() : 0;
                    return $bTime - $aTime;
                });
                $data['recentTasks'] = array_slice($recentTasksList, 0, 5);
            }
            
            // Последние проекты (подразделения + проекты, где пользователь прикреплён к задаче)
            $recentProjects = \app\models\Project::find()
                ->where(['department_id' => $user->department_id])
                ->orderBy(['created_at' => SORT_DESC])
                ->limit(5)
                ->all();
            $data['recentProjects'] = $recentProjects;
        } else {
            // Пользователь без подразделения: показываем только задачи, где он прикреплён, и их проекты
            $userTasks = [];
            $tasksWithMe = \app\models\Task::find()
                ->where([
                    '$or' => [
                        ['executor_user_ids' => $user->_id],
                        ['executor_user_from_department_id' => $user->_id],
                        ['executor_user_from_subdepartment_id' => $user->_id],
                    ],
                ])
                ->all();
            foreach ($tasksWithMe as $t) {
                if ($t->isAssignedToUser($user)) {
                    $userTasks[] = $t;
                }
            }
            $approvedReqs = \app\models\TaskExecutorRequest::find()
                ->where(['user_id' => $user->_id, 'status' => \app\models\TaskExecutorRequest::STATUS_APPROVED])
                ->all();
            foreach ($approvedReqs as $req) {
                $task = \app\models\Task::findOne(['_id' => $req->task_id]);
                if ($task && $task->isAssignedToUser($user)) {
                    $userTasks[] = $task;
                }
            }
            $userTasks = array_unique($userTasks, SORT_REGULAR);
            usort($userTasks, function($a, $b) {
                $aTime = $a->created_at instanceof \MongoDB\BSON\UTCDateTime ? $a->created_at->toDateTime()->getTimestamp() : 0;
                $bTime = $b->created_at instanceof \MongoDB\BSON\UTCDateTime ? $b->created_at->toDateTime()->getTimestamp() : 0;
                return $bTime - $aTime;
            });
            $projectIdsFromTasks = [];
            foreach ($userTasks as $t) {
                if ($t->project_id) {
                    $projectIdsFromTasks[(string)$t->project_id] = true;
                }
            }
            $projectIdsFromTasks = array_keys($projectIdsFromTasks);
            $data['totalProjects'] = count($projectIdsFromTasks);
            $data['activeProjects'] = 0;
            $data['totalTasks'] = count($userTasks);
            $data['myTasks'] = array_slice($userTasks, 0, 5);
            $data['recentTasks'] = $data['myTasks'];
            $data['recentProjects'] = !empty($projectIdsFromTasks)
                ? \app\models\Project::find()
                    ->where(['_id' => ['$in' => array_map(function ($id) {
                        try { return new \MongoDB\BSON\ObjectId($id); } catch (\Exception $e) { return null; }
                    }, $projectIdsFromTasks)]])
                    ->orderBy(['created_at' => SORT_DESC])
                    ->limit(5)
                    ->all()
                : [];
        }
        
        return $this->render('index', $data);
    }

    /**
     * Signup action.
     *
     * @return Response|string
     */
    public function actionSignup()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new SignupForm();
        if ($model->load(Yii::$app->request->post())) {
            if ($model->signup()) {
                Yii::$app->session->setFlash('success', 'Регистрация прошла успешно! Теперь вы можете войти в систему.');
                return $this->redirect(['site/login']);
            } else {
                Yii::$app->session->setFlash('error', 'Ошибка при регистрации. Пожалуйста, проверьте введенные данные.');
            }
        }

        return $this->render('signup', [
            'model' => $model,
        ]);
    }

    /**
     * Login action.
     *
     * @return Response|string
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        }

        $model->password = '';
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Displays contact page.
     *
     * @return Response|string
     */
    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->contact(Yii::$app->params['adminEmail'])) {
            Yii::$app->session->setFlash('contactFormSubmitted');

            return $this->refresh();
        }
        return $this->render('contact', [
            'model' => $model,
        ]);
    }

    /**
     * Displays about page.
     *
     * @return string
     */
    public function actionAbout()
    {
        return $this->render('about');
    }
}
