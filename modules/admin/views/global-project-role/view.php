<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\GlobalProjectRole $model */

$this->title = 'Роль в глобальном проекте: ' . ($model->user ? $model->user->fio : 'Неизвестный пользователь');
$this->params['breadcrumbs'][] = ['label' => 'Админка', 'url' => ['/admin']];
$this->params['breadcrumbs'][] = ['label' => 'Роли в глобальном проекте', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="global-project-role-view">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= Html::encode($this->title) ?></h1>
        <div>
            <?= Html::a('Редактировать', ['update', 'id' => (string)$model->_id], ['class' => 'btn btn-primary']) ?>
            <?= Html::a('Удалить', ['delete', 'id' => (string)$model->_id], [
                'class' => 'btn btn-danger',
                'data' => [
                    'confirm' => 'Вы уверены, что хотите удалить эту роль?',
                    'method' => 'post',
                ],
            ]) ?>
            <?= Html::a('Назад к списку', ['index'], ['class' => 'btn btn-secondary']) ?>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table">
                <tr>
                    <th>Пользователь</th>
                    <td>
                        <?= Html::encode($model->user ? $model->user->fio : 'Неизвестный пользователь') ?>
                        <br>
                        <small class="text-muted"><?= Html::encode($model->user ? $model->user->email : '') ?></small>
                    </td>
                </tr>
                <tr>
                    <th>Роль</th>
                    <td>
                        <span class="badge bg-primary">
                            <?= Html::encode($model->getRoleLabel()) ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th>Дата назначения</th>
                    <td>
                        <?php if ($model->created_at instanceof \MongoDB\BSON\UTCDateTime): ?>
                            <?= date('d.m.Y H:i', $model->created_at->toDateTime()->getTimestamp()) ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>Дата обновления</th>
                    <td>
                        <?php if ($model->updated_at instanceof \MongoDB\BSON\UTCDateTime): ?>
                            <?= date('d.m.Y H:i', $model->updated_at->toDateTime()->getTimestamp()) ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>
    </div>

</div>

