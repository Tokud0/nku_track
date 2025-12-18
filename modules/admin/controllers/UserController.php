<?php

namespace app\modules\admin\controllers;

use Yii;
use app\models\User;
use app\modules\admin\models\UserSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;

/**
 * UserController implements the CRUD actions for User model.
 */
class UserController extends Controller
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
                ],
            ],
        ];
    }

    /**
     * Lists all User models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new UserSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single User model.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new User model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new User();

        if ($model->load(Yii::$app->request->post())) {
            // Конвертируем department_id в ObjectId если это строка
            if (!empty($_POST['User']['department_id'])) {
                $model->department_id = new \MongoDB\BSON\ObjectId($_POST['User']['department_id']);
            } else {
                $model->department_id = null;
            }
            
            // Конвертируем subdepartment_id в ObjectId если это строка
            if (!empty($_POST['User']['subdepartment_id'])) {
                $model->subdepartment_id = new \MongoDB\BSON\ObjectId($_POST['User']['subdepartment_id']);
            } else {
                $model->subdepartment_id = null;
            }
            
            // Пароль обязателен при создании
            if (empty($model->password)) {
                $model->addError('password', 'Пароль не может быть пустым.');
            } else {
                $model->setPassword($model->password);
            }
            
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Пользователь успешно создан.');
                return $this->redirect(['view', 'id' => (string)$model->_id]);
            }
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing User model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $oldPasswordHash = $model->password_hash;

        if ($model->load(Yii::$app->request->post())) {
            // Конвертируем department_id в ObjectId если это строка
            if (!empty($_POST['User']['department_id'])) {
                $model->department_id = new \MongoDB\BSON\ObjectId($_POST['User']['department_id']);
            } else {
                $model->department_id = null;
            }
            
            // Конвертируем subdepartment_id в ObjectId если это строка
            if (!empty($_POST['User']['subdepartment_id'])) {
                $model->subdepartment_id = new \MongoDB\BSON\ObjectId($_POST['User']['subdepartment_id']);
            } else {
                $model->subdepartment_id = null;
            }
            
            // Если пароль изменен, обновляем хэш
            if (!empty($model->password)) {
                $model->setPassword($model->password);
            } else {
                // Если пароль не указан, оставляем старый
                $model->password_hash = $oldPasswordHash;
            }
            
            // Убираем виртуальное поле password перед сохранением
            $model->password = '';
            
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Пользователь успешно обновлен.');
                return $this->redirect(['view', 'id' => (string)$model->_id]);
            }
        }

        // Очищаем пароль для безопасности
        $model->password = '';

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing User model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        
        // Не позволяем удалять самого себя
        if ((string)$model->_id === (string)Yii::$app->user->id) {
            Yii::$app->session->setFlash('error', 'Вы не можете удалить самого себя.');
            return $this->redirect(['index']);
        }
        
        $model->delete();
        Yii::$app->session->setFlash('success', 'Пользователь успешно удален.');

        return $this->redirect(['index']);
    }

    /**
     * Finds the User model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return User the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = User::findOne(['_id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('Запрашиваемая страница не найдена.');
    }
}

