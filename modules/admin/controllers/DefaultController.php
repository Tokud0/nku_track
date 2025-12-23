<?php

namespace app\modules\admin\controllers;

use Yii;
use yii\web\Controller;
use app\models\User;
use yii\filters\AccessControl;

/**
 * Default controller for the `admin` module
 */
class DefaultController extends Controller
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
        ];
    }

    /**
     * Renders the index view for the module
     * @return string
     */
    public function actionIndex()
    {
        // Статистика для dashboard
        $totalUsers = User::find()->count();
        $totalAdmins = User::find()->where(['role' => User::ROLE_ADMIN])->count();
        $totalHeads = User::find()->where(['role' => User::ROLE_HEAD])->count();
        $totalRectors = User::find()->where(['role' => User::ROLE_RECTOR])->count();
        $totalTopManagers = User::find()->where(['role' => User::ROLE_TOP_MANAGER])->count();
        $totalManagers = User::find()->where(['role' => User::ROLE_MANAGER])->count();
        $totalExecutors = User::find()->where(['role' => User::ROLE_EXECUTOR])->count();

        return $this->render('index', [
            'totalUsers' => $totalUsers,
            'totalAdmins' => $totalAdmins,
            'totalHeads' => $totalHeads,
            'totalRectors' => $totalRectors,
            'totalTopManagers' => $totalTopManagers,
            'totalManagers' => $totalManagers,
            'totalExecutors' => $totalExecutors,
        ]);
    }
}

