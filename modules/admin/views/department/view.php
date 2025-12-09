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
        <?= Html::a('Управление пользователями', ['manage-users', 'id' => (string)$model->_id], ['class' => 'btn btn-success']) ?>
        <?= Html::a('Удалить', ['delete', 'id' => (string)$model->_id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Вы уверены, что хотите удалить это подразделение?',
                'method' => 'post',
            ],
        ]) ?>
        <?= Html::a('Назад к списку', ['index'], ['class' => 'btn btn-secondary']) ?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            '_id',
            'name',
            'description:ntext',
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

    <h3>Пользователи подразделения</h3>
    <?php
    $users = \app\models\User::find()->where(['department_id' => $model->_id])->all();
    if (empty($users)): ?>
        <p class="text-muted">В этом подразделении нет пользователей.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($users as $user): ?>
                <li><?= Html::a(Html::encode($user->fio), ['/admin/user/view', 'id' => (string)$user->_id]) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

</div>

