<?php

namespace app\modules\admin;

use Yii;

/**
 * admin module definition class
 */
class Module extends \yii\base\Module
{
    /**
     * {@inheritdoc}
     */
    public $controllerNamespace = 'app\modules\admin\controllers';

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();

        // Проверяем, что пользователь авторизован и является админом
        if (Yii::$app->user->isGuest || 
            Yii::$app->user->identity->role !== \app\models\User::ROLE_ADMIN) {
            Yii::$app->response->redirect(['/site/login'])->send();
            Yii::$app->end();
        }
        
        // Устанавливаем defaultRoute на главную страницу админки
        $this->defaultRoute = 'default';
    }
}

