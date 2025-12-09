<?php

namespace app\modules\admin\controllers;

use Yii;
use app\models\Department;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use app\models\User;

/**
 * DepartmentController implements the CRUD actions for Department model.
 */
class DepartmentController extends Controller
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
                            return Yii::$app->user->identity->role === User::ROLE_ADMIN || 
                                   Yii::$app->user->identity->role === User::ROLE_RECTOR;
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
     * Lists all Department models.
     * @return mixed
     */
    public function actionIndex()
    {
        $departments = Department::find()->orderBy(['name' => SORT_ASC])->all();

        return $this->render('index', [
            'departments' => $departments,
        ]);
    }

    /**
     * Displays a single Department model.
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
     * Creates a new Department model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Department();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Подразделение успешно создано.');
            return $this->redirect(['view', 'id' => (string)$model->_id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Department model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Подразделение успешно обновлено.');
            return $this->redirect(['view', 'id' => (string)$model->_id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Управление пользователями департамента
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionManageUsers($id)
    {
        $model = $this->findModel($id);
        
        // Получаем всех пользователей, не привязанных к текущему департаменту
        $allUsers = User::find()->all();
        $availableUsers = [];
        foreach ($allUsers as $user) {
            if (!$user->department_id || (string)$user->department_id !== (string)$model->_id) {
                $availableUsers[] = $user;
            }
        }
        
        // Получаем пользователей текущего департамента
        $departmentUsers = User::find()
            ->where(['department_id' => $model->_id])
            ->all();
        
        if (Yii::$app->request->isPost) {
            $selectedUsers = Yii::$app->request->post('users', []);
            
            // Конвертируем строки в ObjectId
            $userIds = [];
            foreach ($selectedUsers as $userId) {
                try {
                    $userIds[] = new \MongoDB\BSON\ObjectId($userId);
                } catch (\Exception $e) {
                    // Пропускаем невалидные ID
                }
            }
            
            // Обновляем всех пользователей: убираем из текущего департамента тех, кто не выбран
            $allDepartmentUsers = User::find()
                ->where(['department_id' => $model->_id])
                ->all();
            
            foreach ($allDepartmentUsers as $user) {
                if (!in_array((string)$user->_id, $selectedUsers)) {
                    $user->department_id = null;
                    $user->save(false);
                }
            }
            
            // Добавляем выбранных пользователей в департамент
            foreach ($userIds as $userId) {
                $user = User::findOne(['_id' => $userId]);
                if ($user) {
                    $user->department_id = $model->_id;
                    $user->save(false);
                }
            }
            
            Yii::$app->session->setFlash('success', 'Пользователи успешно обновлены.');
            return $this->redirect(['view', 'id' => $id]);
        }
        
        return $this->render('manage-users', [
            'model' => $model,
            'availableUsers' => $availableUsers,
            'departmentUsers' => $departmentUsers,
        ]);
    }

    /**
     * Deletes an existing Department model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        
        // Проверяем, есть ли пользователи в этом подразделении
        $usersCount = User::find()->where(['department_id' => $model->_id])->count();
        if ($usersCount > 0) {
            Yii::$app->session->setFlash('error', 'Невозможно удалить подразделение, так как в нем есть пользователи (' . $usersCount . '). Сначала удалите всех пользователей из подразделения.');
            return $this->redirect(['view', 'id' => $id]);
        }
        
        $model->delete();
        Yii::$app->session->setFlash('success', 'Подразделение успешно удалено.');

        return $this->redirect(['index']);
    }

    /**
     * Finds the Department model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return Department the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Department::findOne(['_id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('Подразделение не найдено.');
    }
}

