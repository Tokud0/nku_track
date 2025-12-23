<?php

use yii\helpers\Html;
use app\models\User;

/** @var yii\web\View $this */
/** @var app\models\User $model */

$this->title = 'Мой профиль';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="profile-index">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= Html::encode($this->title) ?></h1>
        <?= Html::a('Редактировать', ['update'], ['class' => 'nku-btn nku-btn--primary']) ?>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="nku-card mb-4">
                <div class="nku-card__header">
                    <h5 class="mb-0">Личная информация</h5>
                </div>
                <div class="nku-card__body">
                    <div class="row mb-3">
                        <div class="col-sm-4">
                            <small class="text-muted d-block mb-1">ФИО</small>
                            <div class="fw-semibold"><?= Html::encode($model->fio) ?></div>
                        </div>
                        <div class="col-sm-4">
                            <small class="text-muted d-block mb-1">Email</small>
                            <div class="fw-semibold"><?= Html::encode($model->email) ?></div>
                        </div>
                        <div class="col-sm-4">
                            <small class="text-muted d-block mb-1">Роль</small>
                            <div>
                                <?php
                                $roles = [
                                    User::ROLE_ADMIN => ['name' => 'Администратор', 'class' => 'danger'],
                                    User::ROLE_HEAD => ['name' => 'Руководитель', 'class' => 'warning'],
                                    User::ROLE_RECTOR => ['name' => 'Ректор', 'class' => 'info'],
                                    User::ROLE_TOP_MANAGER => ['name' => 'Топ-менеджер', 'class' => 'success'],
                                    User::ROLE_MANAGER => ['name' => 'Менеджер', 'class' => 'secondary'],
                                    User::ROLE_EXECUTOR => ['name' => 'Исполнитель', 'class' => 'primary'],
                                ];
                                $roleInfo = $roles[$model->role] ?? ['name' => $model->role, 'class' => 'default'];
                                ?>
                                <span class="badge bg-<?= $roleInfo['class'] ?>"><?= $roleInfo['name'] ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="nku-card mb-4">
                <div class="nku-card__header">
                    <h5 class="mb-0">Подразделение</h5>
                </div>
                <div class="nku-card__body">
                    <div class="row mb-3">
                        <div class="col-sm-6">
                            <small class="text-muted d-block mb-1">Подразделение</small>
                            <div class="fw-semibold">
                                <?= $model->department ? Html::encode($model->department->name) : '<span class="text-muted">Не указано</span>' ?>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <small class="text-muted d-block mb-1">Департамент</small>
                            <div class="fw-semibold">
                                <?= $model->subdepartment ? Html::encode($model->subdepartment->name) : '<span class="text-muted">Не указано</span>' ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="nku-card mb-4">
                <div class="nku-card__header">
                    <h5 class="mb-0">Информация об аккаунте</h5>
                </div>
                <div class="nku-card__body">
                    <div class="mb-3">
                        <small class="text-muted d-block mb-1">Дата регистрации</small>
                        <div>
                            <?php if ($model->created_at instanceof \MongoDB\BSON\UTCDateTime): ?>
                                <?= date('d.m.Y H:i', $model->created_at->toDateTime()->getTimestamp()) ?>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div>
                        <small class="text-muted d-block mb-1">Последнее обновление</small>
                        <div>
                            <?php if ($model->updated_at instanceof \MongoDB\BSON\UTCDateTime): ?>
                                <?= date('d.m.Y H:i', $model->updated_at->toDateTime()->getTimestamp()) ?>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

