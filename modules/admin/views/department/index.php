<?php

use yii\helpers\Html;
use yii\widgets\LinkPager;
use app\models\Department;

/** @var yii\web\View $this */
/** @var app\models\Department[] $departments */

$this->title = 'Подразделения';
$this->params['breadcrumbs'][] = ['label' => 'Админка', 'url' => ['/admin']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="department-index">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= Html::encode($this->title) ?></h1>
        <?= Html::a('Создать подразделение', ['create'], ['class' => 'btn btn-success']) ?>
    </div>

    <?php if (empty($departments)): ?>
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
                        <th>Количество пользователей</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($departments as $department): ?>
                        <tr>
                            <td><?= Html::encode($department->name) ?></td>
                            <td><?= Html::encode($department->description) ?></td>
                            <td>
                                <?php
                                $usersCount = \app\models\User::find()->where(['department_id' => $department->_id])->count();
                                echo $usersCount;
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
    <?php endif; ?>

</div>

