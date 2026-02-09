<?php

namespace app\commands;

use app\models\User;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Команды для работы с пользователями.
 */
class UserController extends Controller
{
    /**
     * Добавляет auth_key существующим пользователям (для работы «Запомнить меня»).
     * @return int
     */
    public function actionAddAuthKeys()
    {
        $users = User::find()->all();
        $count = 0;
        foreach ($users as $user) {
            if (empty($user->auth_key)) {
                $user->auth_key = \Yii::$app->security->generateRandomString();
                $user->save(false);
                $count++;
                $this->stdout("Обновлён пользователь: {$user->email}\n");
            }
        }
        $this->stdout("Добавлено auth_key для {$count} пользователей.\n");
        return ExitCode::OK;
    }
}
