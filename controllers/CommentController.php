<?php

namespace app\controllers;

use Yii;
use app\models\Comment;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;

/**
 * CommentController handles comment creation
 */
class CommentController extends Controller
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
                    'create' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Creates a new Comment model.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Comment();
        $user = Yii::$app->user->identity;
        
        // Ректор не может создавать комментарии
        if ($user->role === \app\models\User::ROLE_RECTOR) {
            Yii::$app->session->setFlash('error', 'Ректор может только просматривать комментарии.');
            return $this->goBack();
        }
        
        $model->author_id = $user->_id;
        
        if ($model->load(Yii::$app->request->post())) {
            // Конвертируем ID в ObjectId если это строки
            if (!empty($_POST['Comment']['project_id'])) {
                $model->project_id = new \MongoDB\BSON\ObjectId($_POST['Comment']['project_id']);
            }
            if (!empty($_POST['Comment']['report_id'])) {
                $model->report_id = new \MongoDB\BSON\ObjectId($_POST['Comment']['report_id']);
            }
            if (!empty($_POST['Comment']['task_id'])) {
                $model->task_id = new \MongoDB\BSON\ObjectId($_POST['Comment']['task_id']);
            }
            
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Комментарий успешно добавлен.');
            } else {
                Yii::$app->session->setFlash('error', 'Ошибка при добавлении комментария.');
            }
        }
        
        // Определяем, куда перенаправить
        if ($model->task_id) {
            return $this->redirect(['task/view', 'id' => (string)$model->task_id]);
        } elseif ($model->project_id) {
            return $this->redirect(['project/view', 'id' => (string)$model->project_id]);
        } elseif ($model->report_id) {
            return $this->redirect(['report/view', 'id' => (string)$model->report_id]);
        }
        
        return $this->goBack();
    }
}

