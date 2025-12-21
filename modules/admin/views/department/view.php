<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use app\models\Department;

/** @var yii\web\View $this */
/** @var app\models\Department $model */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Админка', 'url' => ['/admin']];
$this->params['breadcrumbs'][] = ['label' => 'Подразделения', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="department-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Редактировать', ['update', 'id' => (string)$model->_id], ['class' => 'btn btn-primary']) ?>
        <?php if ($model->isMainDepartment()): ?>
            <?= Html::a('Создать департамент', ['create-subdepartment', 'id' => (string)$model->_id], ['class' => 'btn btn-info']) ?>
        <?php endif; ?>
        <?= Html::a('Управление пользователями', ['manage-users', 'id' => (string)$model->_id], ['class' => 'btn btn-success']) ?>
        <?= Html::a('Дорожная карта', ['/admin/roadmap/view', 'id' => (string)$model->_id], ['class' => 'btn btn-warning']) ?>
        <?= Html::a('Удалить', ['delete', 'id' => (string)$model->_id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Вы уверены, что хотите удалить это подразделение?',
                'method' => 'post',
            ],
        ]) ?>
        <?php if ($model->isSubdepartment()): ?>
            <?= Html::a('К родительскому подразделению', ['view', 'id' => (string)$model->parent_id], ['class' => 'btn btn-secondary']) ?>
        <?php endif; ?>
        <?= Html::a('Назад к списку', ['index'], ['class' => 'btn btn-secondary']) ?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            '_id',
            'name',
            'description:ntext',
            [
                'attribute' => 'parent_id',
                'label' => $model->isSubdepartment() ? 'Родительское подразделение' : 'Тип',
                'value' => function($model) {
                    if ($model->isSubdepartment() && $model->parent) {
                        return Html::a(Html::encode($model->parent->name), ['view', 'id' => (string)$model->parent_id]);
                    }
                    return 'Основное подразделение';
                },
                'format' => 'raw',
            ],
            [
                'attribute' => 'created_at',
                'value' => function($model) {
                    if ($model->created_at instanceof \MongoDB\BSON\UTCDateTime) {
                        return date('d.m.Y H:i', $model->created_at->toDateTime()->getTimestamp());
                    }
                    return '-';
                },
            ],
            [
                'attribute' => 'updated_at',
                'value' => function($model) {
                    if ($model->updated_at instanceof \MongoDB\BSON\UTCDateTime) {
                        return date('d.m.Y H:i', $model->updated_at->toDateTime()->getTimestamp());
                    }
                    return '-';
                },
            ],
        ],
    ]) ?>

    <?php if ($model->isMainDepartment()): ?>
        <h3>Департаменты подразделения</h3>
        <?php
        $subdepartments = $model->getSubdepartments()->orderBy(['name' => SORT_ASC])->all();
        if (empty($subdepartments)): ?>
            <p class="text-muted">В этом подразделении нет департаментов.</p>
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
                        <?php foreach ($subdepartments as $subdepartment): ?>
                            <tr>
                                <td><?= Html::encode($subdepartment->name) ?></td>
                                <td><?= Html::encode($subdepartment->description) ?></td>
                                <td>
                                    <?php
                                    $usersCount = \app\models\User::find()->where(['subdepartment_id' => $subdepartment->_id])->count();
                                    echo $usersCount;
                                    ?>
                                </td>
                                <td>
                                    <?= Html::a('Просмотр', ['view', 'id' => (string)$subdepartment->_id], ['class' => 'btn btn-sm btn-info']) ?>
                                    <?= Html::a('Редактировать', ['update', 'id' => (string)$subdepartment->_id], ['class' => 'btn btn-sm btn-primary']) ?>
                                    <?= Html::a('Удалить', ['delete', 'id' => (string)$subdepartment->_id], [
                                        'class' => 'btn btn-sm btn-danger',
                                        'data-confirm' => 'Вы уверены, что хотите удалить этот департамент?',
                                        'data-method' => 'post',
                                    ]) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <h3><?= $model->isMainDepartment() ? 'Пользователи подразделения' : 'Пользователи департамента' ?></h3>
    <?php
    if ($model->isMainDepartment()) {
        $users = \app\models\User::find()
            ->where(['department_id' => $model->_id])
            ->andWhere(['subdepartment_id' => null])
            ->all();
    } else {
        $users = \app\models\User::find()
            ->where(['subdepartment_id' => $model->_id])
            ->all();
    }
    if (empty($users)): ?>
        <p class="text-muted">В этом <?= $model->isMainDepartment() ? 'подразделении' : 'департаменте' ?> нет пользователей.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($users as $user): ?>
                <li>
                    <?= Html::a(Html::encode($user->fio), ['/admin/user/view', 'id' => (string)$user->_id]) ?>
                    <?php if ($model->isMainDepartment() && $user->subdepartment_id): ?>
                        <small class="text-muted">(департамент: <?= Html::encode($user->subdepartment->name) ?>)</small>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

</div>

