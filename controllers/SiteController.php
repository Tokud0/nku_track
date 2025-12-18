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
        
        // Для всех авторизованных пользователей показываем проекты их подразделения
        if ($user->department_id) {
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
            
            // Для исполнителя показываем только его задачи
            if ($user->role === \app\models\User::ROLE_EXECUTOR) {
                $userTasks = [];
                foreach ($allTasks as $task) {
                    if ($task->isAssignedToUser($user)) {
                        $userTasks[] = $task;
                    }
                }
                $data['totalTasks'] = count($userTasks);
                // Сортируем по дате создания
                usort($userTasks, function($a, $b) {
                    $aTime = $a->created_at instanceof \MongoDB\BSON\UTCDateTime ? $a->created_at->toDateTime()->getTimestamp() : 0;
                    $bTime = $b->created_at instanceof \MongoDB\BSON\UTCDateTime ? $b->created_at->toDateTime()->getTimestamp() : 0;
                    return $bTime - $aTime;
                });
                $data['myTasks'] = array_slice($userTasks, 0, 5);
            } else {
                $data['totalTasks'] = count($allTasks);
                // Сортируем по дате создания
                usort($allTasks, function($a, $b) {
                    $aTime = $a->created_at instanceof \MongoDB\BSON\UTCDateTime ? $a->created_at->toDateTime()->getTimestamp() : 0;
                    $bTime = $b->created_at instanceof \MongoDB\BSON\UTCDateTime ? $b->created_at->toDateTime()->getTimestamp() : 0;
                    return $bTime - $aTime;
                });
                $data['recentTasks'] = array_slice($allTasks, 0, 5);
            }
            
            // Последние проекты
            $data['recentProjects'] = \app\models\Project::find()
                ->where(['department_id' => $user->department_id])
                ->orderBy(['created_at' => SORT_DESC])
                ->limit(5)
                ->all();
        } else {
            $data['totalProjects'] = 0;
            $data['activeProjects'] = 0;
            $data['totalTasks'] = 0;
            $data['recentProjects'] = [];
            $data['recentTasks'] = [];
            $data['myTasks'] = [];
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
