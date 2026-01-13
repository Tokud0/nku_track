<?php

namespace app\modules\admin\controllers;

use Yii;
use app\models\GlobalProjectRole;
use app\models\User;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\web\Response;

/**
 * GlobalProjectRoleController implements the CRUD actions for GlobalProjectRole model.
 */
class GlobalProjectRoleController extends Controller
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
     * Lists all GlobalProjectRole models.
     * @return mixed
     */
    public function actionIndex()
    {
        $roles = GlobalProjectRole::find()->with('user')->all();

        return $this->render('index', [
            'roles' => $roles,
        ]);
    }

    /**
     * Displays a single GlobalProjectRole model.
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
     * Creates a new GlobalProjectRole model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new GlobalProjectRole();

        if ($model->load(Yii::$app->request->post())) {
            // Проверяем и конвертируем user_id в ObjectId
            if (!empty($model->user_id) && is_string($model->user_id)) {
                // Проверяем, что это валидный ObjectId (24 символа hex)
                if (preg_match('/^[0-9a-fA-F]{24}$/', $model->user_id)) {
                    try {
                        $model->user_id = new \MongoDB\BSON\ObjectId($model->user_id);
                    } catch (\Exception $e) {
                        Yii::$app->session->setFlash('error', 'Ошибка: неверный ID пользователя. Пожалуйста, выберите пользователя из списка результатов поиска.');
                        return $this->render('create', [
                            'model' => $model,
                        ]);
                    }
                } else {
                    Yii::$app->session->setFlash('error', 'Ошибка: неверный формат ID пользователя. Пожалуйста, выберите пользователя из списка результатов поиска.');
                    return $this->render('create', [
                        'model' => $model,
                    ]);
                }
            } else {
                Yii::$app->session->setFlash('error', 'Пожалуйста, выберите пользователя.');
                return $this->render('create', [
                    'model' => $model,
                ]);
            }
            
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Роль успешно назначена.');
                return $this->redirect(['view', 'id' => (string)$model->_id]);
            } else {
                Yii::$app->session->setFlash('error', 'Ошибка при сохранении: ' . implode(', ', $model->getFirstErrors()));
            }
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing GlobalProjectRole model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post())) {
            // Проверяем и конвертируем user_id в ObjectId
            if (!empty($model->user_id) && is_string($model->user_id)) {
                // Проверяем, что это валидный ObjectId (24 символа hex)
                if (preg_match('/^[0-9a-fA-F]{24}$/', $model->user_id)) {
                    try {
                        $model->user_id = new \MongoDB\BSON\ObjectId($model->user_id);
                    } catch (\Exception $e) {
                        Yii::$app->session->setFlash('error', 'Ошибка: неверный ID пользователя. Пожалуйста, выберите пользователя из списка результатов поиска.');
                        return $this->render('update', [
                            'model' => $model,
                        ]);
                    }
                } else {
                    Yii::$app->session->setFlash('error', 'Ошибка: неверный формат ID пользователя. Пожалуйста, выберите пользователя из списка результатов поиска.');
                    return $this->render('update', [
                        'model' => $model,
                    ]);
                }
            } else {
                Yii::$app->session->setFlash('error', 'Пожалуйста, выберите пользователя.');
                return $this->render('update', [
                    'model' => $model,
                ]);
            }
            
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Роль успешно обновлена.');
                return $this->redirect(['view', 'id' => (string)$model->_id]);
            } else {
                Yii::$app->session->setFlash('error', 'Ошибка при сохранении: ' . implode(', ', $model->getFirstErrors()));
            }
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing GlobalProjectRole model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();
        Yii::$app->session->setFlash('success', 'Роль успешно удалена.');

        return $this->redirect(['index']);
    }

    /**
     * Поиск пользователей (AJAX)
     * @return array
     */
    public function actionSearchUsers()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $query = trim(Yii::$app->request->get('q', ''));
        $limit = 20;
        
        if (empty($query) || strlen($query) < 3) {
            return ['results' => []];
        }
        
        try {
            // Используем регулярное выражение для поиска
            $escapedQuery = preg_quote($query, '/');
            $regex = new \MongoDB\BSON\Regex($escapedQuery, 'i');
            
            // Поиск по ФИО или email используя orWhere
            $queryBuilder = User::find();
            
            // Используем where с условием $or для MongoDB
            $users = $queryBuilder
                ->where([
                    '$or' => [
                        ['fio' => $regex],
                        ['email' => $regex]
                    ]
                ])
                ->orderBy(['fio' => SORT_ASC])
                ->limit($limit)
                ->all();
            
            $results = [];
            foreach ($users as $user) {
                if ($user->fio && $user->email) {
                    $results[] = [
                        'id' => (string)$user->_id,
                        'text' => $user->fio . ' (' . $user->email . ')',
                    ];
                }
            }
            
            return ['results' => $results];
        } catch (\Exception $e) {
            Yii::error('Error searching users: ' . $e->getMessage());
            Yii::error('Stack trace: ' . $e->getTraceAsString());
            return [
                'results' => [],
                'error' => YII_DEBUG ? $e->getMessage() : 'Ошибка при поиске пользователей'
            ];
        }
    }

    /**
     * Finds the GlobalProjectRole model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return GlobalProjectRole the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = GlobalProjectRole::findOne(['_id' => new \MongoDB\BSON\ObjectId($id)])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('Роль не найдена.');
    }
}

