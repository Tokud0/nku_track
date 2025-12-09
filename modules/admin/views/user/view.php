<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use app\models\User;

/** @var yii\web\View $this */
/** @var app\models\User $model */

$this->title = $model->fio;
$this->params['breadcrumbs'][] = ['label' => 'Админка', 'url' => ['/admin']];
$this->params['breadcrumbs'][] = ['label' => 'Пользователи', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="user-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Редактировать', ['update', 'id' => (string)$model->_id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Удалить', ['delete', 'id' => (string)$model->_id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Вы уверены, что хотите удалить этого пользователя?',
                'method' => 'post',
            ],
        ]) ?>
        <?= Html::a('Назад к списку', ['index'], ['class' => 'btn btn-secondary']) ?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            '_id',
            'fio',
            'email:email',
            [
                'attribute' => 'role',
                'value' => function($model) {
                    $roles = [
                        User::ROLE_ADMIN => 'Администратор',
                        User::ROLE_RECTOR => 'Ректор',
                        User::ROLE_MANAGER => 'Руководитель',
                        User::ROLE_EXECUTOR => 'Исполнитель',
                    ];
                    return $roles[$model->role] ?? $model->role;
                },
            ],
            [
                'attribute' => 'department_id',
                'label' => 'Подразделение',
                'value' => function($model) {
                    return $model->department ? $model->department->name : '-';
                },
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

</div>

