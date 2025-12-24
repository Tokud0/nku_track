<?php

namespace app\modules\admin\controllers;

use Yii;
use app\models\Department;
use app\modules\admin\models\DepartmentSearch;
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
     * Lists all Department models (only main departments).
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new DepartmentSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
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
     * Creates a new Department model (main department).
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Department();
        $model->parent_id = null; // Основное подразделение

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Подразделение успешно создано.');
            return $this->redirect(['view', 'id' => (string)$model->_id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Creates a new subdepartment (department inside a main department).
     * @param string $id ID родительского подразделения
     * @return mixed
     * @throws NotFoundHttpException if the parent model cannot be found
     */
    public function actionCreateSubdepartment($id)
    {
        $parent = $this->findModel($id);
        
        // Проверяем, что это основное подразделение
        if ($parent->isSubdepartment()) {
            Yii::$app->session->setFlash('error', 'Нельзя создать департамент внутри департамента.');
            return $this->redirect(['view', 'id' => $id]);
        }

        $model = new Department();
        $model->parent_id = $parent->_id;

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Департамент успешно создан.');
            return $this->redirect(['view', 'id' => (string)$parent->_id]);
        }

        return $this->render('create-subdepartment', [
            'model' => $model,
            'parent' => $parent,
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
     * Управление пользователями подразделения/департамента
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionManageUsers($id)
    {
        $model = $this->findModel($id);
        
        // Получаем всех пользователей
        $allUsers = User::find()->all();
        $availableUsers = [];
        
        // Определяем, какие пользователи доступны для привязки
        foreach ($allUsers as $user) {
            $canAdd = false;
            
            if ($model->isMainDepartment()) {
                // Для основного подразделения: пользователь не должен быть привязан к этому подразделению
                if (!$user->department_id || (string)$user->department_id !== (string)$model->_id) {
                    $canAdd = true;
                }
            } else {
                // Для департамента: пользователь должен быть привязан к родительскому подразделению
                // и не должен быть привязан к другому департаменту
                if ($user->department_id && (string)$user->department_id === (string)$model->parent_id) {
                    if (!$user->subdepartment_id || (string)$user->subdepartment_id === (string)$model->_id) {
                        $canAdd = true;
                    }
                }
            }
            
            if ($canAdd) {
                $availableUsers[] = $user;
            }
        }
        
        // Получаем пользователей текущего подразделения/департамента
        if ($model->isMainDepartment()) {
            $departmentUsers = User::find()
                ->where(['department_id' => $model->_id, 'subdepartment_id' => null])
                ->all();
        } else {
            $departmentUsers = User::find()
                ->where(['subdepartment_id' => $model->_id])
                ->all();
        }
        
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
            
            // Обновляем всех пользователей: убираем из текущего подразделения/департамента тех, кто не выбран
            foreach ($departmentUsers as $user) {
                if (!in_array((string)$user->_id, $selectedUsers)) {
                    if ($model->isMainDepartment()) {
                        $user->department_id = null;
                        $user->subdepartment_id = null;
                    } else {
                        $user->subdepartment_id = null;
                    }
                    $user->save(false);
                }
            }
            
            // Добавляем выбранных пользователей в подразделение/департамент
            foreach ($userIds as $userId) {
                $user = User::findOne(['_id' => $userId]);
                if ($user) {
                    if ($model->isMainDepartment()) {
                        $user->department_id = $model->_id;
                        $user->subdepartment_id = null;
                    } else {
                        // Для департамента: проверяем, что пользователь привязан к родительскому подразделению
                        if (!$user->department_id || (string)$user->department_id !== (string)$model->parent_id) {
                            $user->department_id = $model->parent_id;
                        }
                        $user->subdepartment_id = $model->_id;
                    }
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
        
        // Проверяем, есть ли дочерние департаменты
        $subdepartmentsCount = Department::find()->where(['parent_id' => $model->_id])->count();
        if ($subdepartmentsCount > 0) {
            Yii::$app->session->setFlash('error', 'Невозможно удалить подразделение, так как в нем есть департаменты (' . $subdepartmentsCount . '). Сначала удалите все департаменты.');
            return $this->redirect(['view', 'id' => $id]);
        }
        
        // Проверяем, есть ли пользователи в этом подразделении/департаменте
        if ($model->isMainDepartment()) {
            $usersCount = User::find()
                ->where(['department_id' => $model->_id])
                ->andWhere(['subdepartment_id' => null])
                ->count();
        } else {
            $usersCount = User::find()->where(['subdepartment_id' => $model->_id])->count();
        }
        
        if ($usersCount > 0) {
            Yii::$app->session->setFlash('error', 'Невозможно удалить подразделение, так как в нем есть пользователи (' . $usersCount . '). Сначала удалите всех пользователей.');
            return $this->redirect(['view', 'id' => $id]);
        }
        
        $model->delete();
        Yii::$app->session->setFlash('success', 'Подразделение успешно удалено.');

        if ($model->isSubdepartment() && $model->parent_id) {
            return $this->redirect(['view', 'id' => (string)$model->parent_id]);
        }

        return $this->redirect(['index']);
    }

    /**
     * Обновление роли пользователя в подразделении
     * @return array
     */
    public function actionUpdateUserRole()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $userId = Yii::$app->request->post('user_id');
        $departmentId = Yii::$app->request->post('department_id');
        $newRole = Yii::$app->request->post('role');
        
        if (!$userId || !$departmentId || !$newRole) {
            return [
                'success' => false,
                'message' => 'Не указаны необходимые параметры'
            ];
        }
        
        // Проверяем валидность роли (админа нельзя назначить через этот интерфейс)
        $validRoles = [
            User::ROLE_HEAD,
            User::ROLE_RECTOR,
            User::ROLE_TOP_MANAGER,
            User::ROLE_MANAGER,
            User::ROLE_EXECUTOR
        ];
        
        if (!in_array($newRole, $validRoles)) {
            return [
                'success' => false,
                'message' => 'Неверная роль. Администратора нельзя назначить через этот интерфейс.'
            ];
        }
        
        // Находим пользователя
        try {
            $user = User::findOne(['_id' => new \MongoDB\BSON\ObjectId($userId)]);
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Пользователь не найден'
                ];
            }
            
            // Нельзя изменить роль администратора
            if ($user->role === User::ROLE_ADMIN) {
                return [
                    'success' => false,
                    'message' => 'Нельзя изменить роль администратора'
                ];
            }
            
            // Проверяем, что пользователь принадлежит указанному подразделению
            $department = Department::findOne(['_id' => new \MongoDB\BSON\ObjectId($departmentId)]);
            if (!$department) {
                return [
                    'success' => false,
                    'message' => 'Подразделение не найдено'
                ];
            }
            
            $belongsToDepartment = false;
            if ($department->isMainDepartment()) {
                $belongsToDepartment = (string)$user->department_id === (string)$department->_id && !$user->subdepartment_id;
            } else {
                $belongsToDepartment = (string)$user->subdepartment_id === (string)$department->_id;
            }
            
            if (!$belongsToDepartment) {
                return [
                    'success' => false,
                    'message' => 'Пользователь не принадлежит этому подразделению'
                ];
            }
            
            // Сохраняем старую роль на случай ошибки
            $oldRole = $user->role;
            
            // Обновляем роль
            $user->role = $newRole;
            if ($user->save(false)) {
                return [
                    'success' => true,
                    'message' => 'Роль успешно обновлена'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Ошибка при сохранении',
                    'oldRole' => $oldRole
                ];
            }
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка: ' . $e->getMessage()
            ];
        }
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

