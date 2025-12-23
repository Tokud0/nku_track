<?php

namespace app\controllers;

use Yii;
use app\models\User;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;

/**
 * ProfileController handles user profile viewing and editing
 */
class ProfileController extends Controller
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
        ];
    }

    /**
     * Displays user profile
     * @return string
     */
    public function actionIndex()
    {
        $model = Yii::$app->user->identity;
        
        return $this->render('index', [
            'model' => $model,
        ]);
    }

    /**
     * Updates user profile
     * @return string|\yii\web\Response
     */
    public function actionUpdate()
    {
        $model = Yii::$app->user->identity;
        
        // Сохраняем старые значения полей, которые нельзя редактировать
        $oldPasswordHash = $model->password_hash;
        $oldRole = $model->role;
        $oldDepartmentId = $model->department_id;
        $oldSubdepartmentId = $model->subdepartment_id;
        
        if ($model->load(Yii::$app->request->post())) {
            // Обработка пароля
            if (!empty($model->password)) {
                $model->setPassword($model->password);
            } else {
                // Если пароль не указан, восстанавливаем старый
                $model->password_hash = $oldPasswordHash;
            }
            
            // Пользователь может редактировать только свои данные
            // Роль, подразделение и департамент не редактируются через профиль
            $model->role = $oldRole;
            $model->department_id = $oldDepartmentId;
            $model->subdepartment_id = $oldSubdepartmentId;
            
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Профиль успешно обновлен.');
                return $this->redirect(['index']);
            } else {
                Yii::$app->session->setFlash('error', 'Ошибка при обновлении профиля.');
            }
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }
}

