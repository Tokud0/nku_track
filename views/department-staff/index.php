<?php

use yii\helpers\Html;
use app\models\User;
use app\models\Department;

/** @var yii\web\View $this */
/** @var Department $department */
/** @var User[] $directStaff */
/** @var array $subdepartmentsWithStaff array of ['department' => Department, 'users' => User[]] */

$this->title = 'Состав подразделения';
$this->params['breadcrumbs'][] = $this->title;

$roleLabels = [
    User::ROLE_ADMIN => ['label' => 'Администратор', 'class' => 'danger'],
    User::ROLE_HEAD => ['label' => 'Руководитель', 'class' => 'warning'],
    User::ROLE_RECTOR => ['label' => 'Ректор', 'class' => 'info'],
    User::ROLE_TOP_MANAGER => ['label' => 'Топ-менеджер', 'class' => 'info'],
    User::ROLE_MANAGER => ['label' => 'Менеджер', 'class' => 'primary'],
    User::ROLE_EXECUTOR => ['label' => 'Исполнитель', 'class' => 'secondary'],
];
?>
<div class="department-staff-index">

    <div class="mb-4">
        <h1 class="mb-2"><?= Html::encode($this->title) ?></h1>
        <p class="text-muted mb-0">Актуальная информация о вашем подразделении и его департаментах</p>
    </div>

    <!-- Подразделение: название и описание -->
    <div class="nku-card mb-4">
        <div class="nku-card__header d-flex align-items-center gap-2">
            <i class="fas fa-building" aria-hidden="true"></i>
            <h5 class="mb-0"><?= Html::encode($department->name) ?></h5>
        </div>
        <div class="nku-card__body">
            <?php if (!empty(trim((string)$department->description))): ?>
                <p class="text-secondary mb-0"><?= nl2br(Html::encode($department->description)) ?></p>
            <?php else: ?>
                <p class="text-muted mb-0">Описание не указано</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Сотрудники напрямую в подразделении (без департамента) -->
    <div class="nku-card mb-4">
        <div class="nku-card__header d-flex align-items-center justify-content-between">
            <h5 class="mb-0">Сотрудники подразделения</h5>
            <span class="text-muted small"><?= count($directStaff) ?> чел.</span>
        </div>
        <div class="nku-card__body p-0">
            <?php if (empty($directStaff)): ?>
                <div class="p-4 text-muted text-center">В подразделении нет сотрудников без привязки к департаменту.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ФИО</th>
                                <th>Email</th>
                                <th>Роль</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($directStaff as $u): ?>
                                <?php $roleInfo = $roleLabels[$u->role] ?? ['label' => $u->role, 'class' => 'secondary']; ?>
                                <tr>
                                    <td class="fw-semibold"><?= Html::encode($u->fio) ?></td>
                                    <td><a href="mailto:<?= Html::encode($u->email) ?>"><?= Html::encode($u->email) ?></a></td>
                                    <td>
                                        <span class="nku-badge nku-badge--<?= $roleInfo['class'] ?>"><?= Html::encode($roleInfo['label']) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Департаменты с составом -->
    <h5 class="mb-3">Департаменты</h5>
    <?php if (empty($subdepartmentsWithStaff)): ?>
        <div class="nku-card">
            <div class="nku-card__body text-muted text-center py-4">
                У подразделения нет департаментов.
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($subdepartmentsWithStaff as $item): ?>
            <?php $sub = $item['department']; $users = $item['users']; ?>
            <div class="nku-card mb-4">
                <div class="nku-card__header d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-sitemap" aria-hidden="true"></i>
                        <h5 class="mb-0"><?= Html::encode($sub->name) ?></h5>
                    </div>
                    <span class="text-muted small"><?= count($users) ?> чел.</span>
                </div>
                <?php if (!empty(trim((string)$sub->description))): ?>
                    <div class="nku-card__body pt-0">
                        <p class="text-secondary small mb-0"><?= nl2br(Html::encode($sub->description)) ?></p>
                    </div>
                <?php endif; ?>
                <div class="nku-card__body <?= !empty(trim((string)$sub->description)) ? 'pt-0' : '' ?> p-0">
                    <?php if (empty($users)): ?>
                        <div class="p-4 text-muted text-center small">В департаменте нет сотрудников.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ФИО</th>
                                        <th>Email</th>
                                        <th>Роль</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $u): ?>
                                        <?php $roleInfo = $roleLabels[$u->role] ?? ['label' => $u->role, 'class' => 'secondary']; ?>
                                        <tr>
                                            <td class="fw-semibold"><?= Html::encode($u->fio) ?></td>
                                            <td><a href="mailto:<?= Html::encode($u->email) ?>"><?= Html::encode($u->email) ?></a></td>
                                            <td>
                                                <span class="nku-badge nku-badge--<?= $roleInfo['class'] ?>"><?= Html::encode($roleInfo['label']) ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>
