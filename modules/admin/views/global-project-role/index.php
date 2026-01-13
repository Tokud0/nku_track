<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var app\models\GlobalProjectRole[] $roles */

$this->title = 'Роли в глобальном проекте';
$this->params['breadcrumbs'][] = ['label' => 'Админка', 'url' => ['/admin']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="global-project-role-index">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= Html::encode($this->title) ?></h1>
        <?= Html::a('Назначить роль', ['create'], ['class' => 'btn btn-success']) ?>
    </div>

    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Пользователь</th>
                    <th>Роль</th>
                    <th>Дата назначения</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($roles)): ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted">Роли не назначены</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($roles as $role): ?>
                        <tr>
                            <td>
                                <?= Html::encode($role->user ? $role->user->fio : 'Неизвестный пользователь') ?>
                                <br>
                                <small class="text-muted"><?= Html::encode($role->user ? $role->user->email : '') ?></small>
                            </td>
                            <td>
                                <span class="badge bg-primary">
                                    <?= Html::encode($role->getRoleLabel()) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($role->created_at instanceof \MongoDB\BSON\UTCDateTime): ?>
                                    <?= date('d.m.Y H:i', $role->created_at->toDateTime()->getTimestamp()) ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= Html::a('Просмотр', ['view', 'id' => (string)$role->_id], ['class' => 'btn btn-sm btn-outline-primary']) ?>
                                <?= Html::a('Редактировать', ['update', 'id' => (string)$role->_id], ['class' => 'btn btn-sm btn-outline-secondary']) ?>
                                <?= Html::a('Удалить', ['delete', 'id' => (string)$role->_id], [
                                    'class' => 'btn btn-sm btn-outline-danger',
                                    'data' => [
                                        'confirm' => 'Вы уверены, что хотите удалить эту роль?',
                                        'method' => 'post',
                                    ],
                                ]) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

