<?php

namespace app\controllers;

use Yii;
use app\models\Project;
use app\models\ProjectSpec;
use app\models\Task;
use app\models\GlobalProjectRole;
use app\models\User;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;

/**
 * GlobalProjectController implements the CRUD actions for Global Project model.
 */
class GlobalProjectController extends Controller
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
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Displays the global project.
     * @return mixed
     */
    public function actionIndex()
    {
        // Получаем глобальный проект (department_id = null)
        $model = Project::findOne(['department_id' => null]);
        
        if (!$model) {
            // Если глобального проекта нет, создаем его
            $user = Yii::$app->user->identity;
            
            // Только админ может создать глобальный проект
            if ($user->role !== User::ROLE_ADMIN) {
                Yii::$app->session->setFlash('error', 'Глобальный проект еще не создан. Обратитесь к администратору.');
                return $this->redirect(['/site/index']);
            }
            
            // Создаем глобальный проект
            $model = new Project();
            $model->title = 'Глобальный проект университета';
            $model->description = 'Глобальный проект для всего университета';
            $model->status = Project::STATUS_ACTIVE;
            $model->department_id = null; // Глобальный проект не привязан к департаменту
            
            // Назначаем ректора как менеджера, если он есть
            $rector = User::findOne(['role' => User::ROLE_RECTOR]);
            if ($rector) {
                $model->manager_id = $rector->_id;
            } else {
                // Если ректора нет, используем текущего админа
                $model->manager_id = $user->_id;
            }
            
            if (!$model->save()) {
                Yii::$app->session->setFlash('error', 'Ошибка при создании глобального проекта.');
                return $this->redirect(['/site/index']);
            }
        }
        
        return $this->redirect(['view', 'id' => (string)$model->_id]);
    }

    /**
     * Displays a single Global Project model.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        
        // Проверяем, что это глобальный проект
        if (!$model->isGlobal()) {
            throw new NotFoundHttpException('Проект не найден.');
        }
        
        // Загружаем ТЗ для проекта
        $spec = ProjectSpec::findOne(['project_id' => $model->_id]);
        
        // Проверяем роль текущего пользователя в глобальном проекте
        $user = Yii::$app->user->identity;
        $userGlobalRole = GlobalProjectRole::getUserRole($user->_id);
        
        return $this->render('view', [
            'model' => $model,
            'spec' => $spec,
            'userGlobalRole' => $userGlobalRole,
        ]);
    }

    /**
     * Updates an existing Global Project model.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        
        // Проверяем, что это глобальный проект
        if (!$model->isGlobal()) {
            throw new NotFoundHttpException('Проект не найден.');
        }
        
        $user = Yii::$app->user->identity;
        
        // Только админ или ректор в глобальном проекте может редактировать
        $userGlobalRole = GlobalProjectRole::getUserRole($user->_id);
        if ($user->role !== User::ROLE_ADMIN && $userGlobalRole !== GlobalProjectRole::ROLE_RECTOR) {
            Yii::$app->session->setFlash('error', 'У вас нет прав для редактирования глобального проекта.');
            return $this->redirect(['view', 'id' => $id]);
        }
        
        if ($model->load(Yii::$app->request->post())) {
            // Убеждаемся, что проект остается глобальным
            $model->department_id = null;
            
            // Обрабатываем даты
            if (!empty($_POST['Project']['start_date_str'])) {
                $model->start_date = new \MongoDB\BSON\UTCDateTime(strtotime($_POST['Project']['start_date_str']) * 1000);
            }
            if (!empty($_POST['Project']['end_date_str'])) {
                $model->end_date = new \MongoDB\BSON\UTCDateTime(strtotime($_POST['Project']['end_date_str']) * 1000);
            }
            
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Глобальный проект успешно обновлен.');
                return $this->redirect(['view', 'id' => $id]);
            }
        }
        
        // Конвертируем даты для формы
        if ($model->start_date instanceof \MongoDB\BSON\UTCDateTime) {
            $model->start_date_str = date('Y-m-d', $model->start_date->toDateTime()->getTimestamp());
        }
        if ($model->end_date instanceof \MongoDB\BSON\UTCDateTime) {
            $model->end_date_str = date('Y-m-d', $model->end_date->toDateTime()->getTimestamp());
        }
        
        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Finds the Project model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return Project the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Project::findOne(['_id' => new \MongoDB\BSON\ObjectId($id)])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('Проект не найден.');
    }
}

