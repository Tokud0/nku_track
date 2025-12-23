<?php

use yii\helpers\Html;
use yii\widgets\Pjax;
use app\models\Department;

/** @var yii\web\View $this */
/** @var app\modules\admin\models\DepartmentSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Подразделения';
$this->params['breadcrumbs'][] = ['label' => 'Админка', 'url' => ['/admin']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="department-index">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= Html::encode($this->title) ?></h1>
        <?= Html::a('Создать подразделение', ['create'], ['class' => 'btn btn-success']) ?>
    </div>

    <?php Pjax::begin(); ?>
    <?php echo $this->render('_search', ['model' => $searchModel]); ?>

    <?php if ($dataProvider->getTotalCount() == 0): ?>
        <div class="alert alert-info">
            Подразделения не найдены.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Название</th>
                        <th>Описание</th>
                        <th>Департаментов</th>
                        <th>Количество пользователей</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dataProvider->getModels() as $department): ?>
                        <tr>
                            <td><?= Html::encode($department->name) ?></td>
                            <td><?= Html::encode($department->description) ?></td>
                            <td>
                                <?php
                                $subdepartmentsCount = \app\models\Department::find()->where(['parent_id' => $department->_id])->count();
                                echo $subdepartmentsCount;
                                ?>
                            </td>
                            <td>
                                <?php
                                // Пользователи напрямую в подразделении
                                $directUsersCount = \app\models\User::find()
                                    ->where(['department_id' => $department->_id])
                                    ->andWhere(['subdepartment_id' => null])
                                    ->count();
                                // Пользователи в департаментах этого подразделения
                                $subdepartments = \app\models\Department::find()
                                    ->where(['parent_id' => $department->_id])
                                    ->all();
                                $subdepartmentIds = [];
                                foreach ($subdepartments as $subdept) {
                                    $subdepartmentIds[] = $subdept->_id;
                                }
                                $subdepartmentUsersCount = 0;
                                if (!empty($subdepartmentIds)) {
                                    $subdepartmentUsersCount = \app\models\User::find()
                                        ->where(['subdepartment_id' => ['$in' => $subdepartmentIds]])
                                        ->count();
                                }
                                $totalUsersCount = $directUsersCount + $subdepartmentUsersCount;
                                echo $totalUsersCount;
                                ?>
                            </td>
                            <td>
                                <?= Html::a('Просмотр', ['view', 'id' => (string)$department->_id], ['class' => 'btn btn-sm btn-info']) ?>
                                <?= Html::a('Редактировать', ['update', 'id' => (string)$department->_id], ['class' => 'btn btn-sm btn-primary']) ?>
                                <?= Html::a('Удалить', ['delete', 'id' => (string)$department->_id], [
                                    'class' => 'btn btn-sm btn-danger',
                                    'data-confirm' => 'Вы уверены, что хотите удалить это подразделение?',
                                    'data-method' => 'post',
                                ]) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php
        echo \yii\widgets\LinkPager::widget([
            'pagination' => $dataProvider->pagination,
        ]);
        ?>
    <?php endif; ?>

    <?php Pjax::end(); ?>

</div>

