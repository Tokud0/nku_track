<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\models\User;
use app\models\Project;
use app\models\Task;

/** @var yii\web\View $this */
/** @var app\models\User $user */
/** @var int $totalProjects */
/** @var int $activeProjects */
/** @var int $totalTasks */
/** @var app\models\Project[] $recentProjects */
/** @var app\models\Task[] $recentTasks */
/** @var app\models\Task[] $myTasks */

$this->title = 'Главная';
$this->params['breadcrumbs'] = [];

// Маппинг ролей для отображения
$roleLabels = [
    User::ROLE_ADMIN => ['label' => 'Администратор', 'icon' => 'fa-shield-alt', 'class' => 'danger'],
    User::ROLE_HEAD => ['label' => 'Руководитель', 'icon' => 'fa-crown', 'class' => 'warning'],
    User::ROLE_RECTOR => ['label' => 'Ректор', 'icon' => 'fa-eye', 'class' => 'info'],
    User::ROLE_TOP_MANAGER => ['label' => 'Топ-менеджер', 'icon' => 'fa-star', 'class' => 'info'],
    User::ROLE_MANAGER => ['label' => 'Менеджер', 'icon' => 'fa-user-tie', 'class' => 'primary'],
    User::ROLE_EXECUTOR => ['label' => 'Исполнитель', 'icon' => 'fa-user', 'class' => 'secondary'],
];

if (Yii::$app->user->isGuest) {
    ?>
    <div class="site-index-guest">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="nku-card text-center" style="margin-top: 4rem;">
                    <div class="nku-card__body py-5">
                        <div class="mb-4">
                            <i class="fas fa-tasks" style="font-size: 4rem; color: var(--nku-color-primary);"></i>
                        </div>
                        <h1 class="display-4 mb-3">Добро пожаловать в KU Track</h1>
                        <p class="lead text-muted mb-4">
                            Система управления проектами и задачами для эффективной работы вашей команды
                        </p>
                        <div class="d-flex gap-3 justify-content-center">
                            <?= Html::a(
                                '<i class="fas fa-sign-in-alt me-2"></i>Войти', 
                                ['/site/login'], 
                                ['class' => 'nku-btn nku-btn--primary nku-btn--lg']
                            ) ?>
                            <?= Html::a(
                                '<i class="fas fa-user-plus me-2"></i>Регистрация', 
                                ['/site/signup'], 
                                ['class' => 'nku-btn nku-btn--secondary nku-btn--lg']
                            ) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
    return;
}

$user = Yii::$app->user->identity;
$currentRole = $roleLabels[$user->role] ?? ['label' => $user->role, 'icon' => 'fa-user', 'class' => 'secondary'];
?>

<div class="site-index">
    <!-- Приветствие с ролью -->
    <div class="nku-card mb-4">
        <div class="nku-card__body">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h2 class="mb-2">Добро пожаловать, <?= Html::encode($user->fio) ?>!</h2>
                    <p class="text-muted mb-2">
                        <i class="fas fa-envelope me-2"></i><?= Html::encode($user->email) ?>
                    </p>
                    <div class="d-flex align-items-center gap-2">
                        <span class="nku-badge nku-badge--<?= $currentRole['class'] ?>">
                            <i class="fas <?= $currentRole['icon'] ?> me-1"></i>
                            <?= $currentRole['label'] ?>
                        </span>
                        <?php if ($user->department): ?>
                            <span class="text-muted">
                                <i class="fas fa-building me-1"></i>
                                <?= Html::encode($user->department->name) ?>
                            </span>
                        <?php elseif ($user->role !== User::ROLE_ADMIN && $user->role !== User::ROLE_RECTOR): ?>
                            <span class="badge bg-warning text-dark">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                Вы не прикреплены ни к одному подразделению
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="text-end">
                    <small class="text-muted">
                        <i class="far fa-clock me-1"></i>
                        <?= date('d.m.Y, H:i') ?>
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- KPI Виджеты -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="nku-card nku-card--hoverable">
                <div class="nku-card__body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center" 
                                 style="width: 56px; height: 56px; background: var(--nku-color-primary-light);">
                                <i class="fas fa-project-diagram" style="font-size: 24px; color: var(--nku-color-primary);"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1">Всего проектов</h6>
                            <h2 class="mb-0"><?= $totalProjects ?? 0 ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-3">
            <div class="nku-card nku-card--hoverable">
                <div class="nku-card__body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center" 
                                 style="width: 56px; height: 56px; background: var(--nku-color-success-light);">
                                <i class="fas fa-check-circle" style="font-size: 24px; color: var(--nku-color-success);"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1">Активных проектов</h6>
                            <h2 class="mb-0"><?= $activeProjects ?? 0 ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-3">
            <div class="nku-card nku-card--hoverable">
                <div class="nku-card__body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center" 
                                 style="width: 56px; height: 56px; background: var(--nku-color-info-light);">
                                <i class="fas fa-tasks" style="font-size: 24px; color: var(--nku-color-info);"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1">Всего задач</h6>
                            <h2 class="mb-0"><?= $totalTasks ?? 0 ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Быстрые действия -->
    <div class="nku-card mb-4">
        <div class="nku-card__header">
            <h5 class="mb-0">
                <i class="fas fa-bolt me-2"></i>
                Быстрые действия
            </h5>
        </div>
        <div class="nku-card__body">
            <div class="row">
                <?php if (in_array($user->role, [User::ROLE_HEAD, User::ROLE_TOP_MANAGER, User::ROLE_ADMIN]) && $user->role !== User::ROLE_RECTOR): ?>
                    <div class="col-md-3 col-sm-6 mb-2">
                        <?= Html::a(
                            '<i class="fas fa-plus-circle me-2"></i>Создать проект', 
                            ['/project/create'], 
                            ['class' => 'nku-btn nku-btn--primary nku-btn--block']
                        ) ?>
                    </div>
                <?php endif; ?>
                
                <?php if (in_array($user->role, [User::ROLE_MANAGER, User::ROLE_TOP_MANAGER, User::ROLE_HEAD, User::ROLE_ADMIN])): ?>
                    <?php if (!empty($recentProjects)): ?>
                        <div class="col-md-3 col-sm-6 mb-2">
                            <?= Html::a(
                                '<i class="fas fa-plus me-2"></i>Создать задачу', 
                                ['/task/create', 'project_id' => (string)$recentProjects[0]->_id], 
                                ['class' => 'nku-btn nku-btn--success nku-btn--block']
                            ) ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                
                <div class="col-md-3 col-sm-6 mb-2">
                    <?= Html::a(
                        '<i class="fas fa-list me-2"></i>Все проекты', 
                        ['/project/index'], 
                        ['class' => 'nku-btn nku-btn--outline-primary nku-btn--block']
                    ) ?>
                </div>
                
                <?php if ($user->role === User::ROLE_ADMIN): ?>
                    <div class="col-md-3 col-sm-6 mb-2">
                        <?= Html::a(
                            '<i class="fas fa-cog me-2"></i>Админка', 
                            ['/admin'], 
                            ['class' => 'nku-btn nku-btn--secondary nku-btn--block']
                        ) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Последние проекты (скрыто для executor) -->
        <?php if ($user->role !== User::ROLE_EXECUTOR): ?>
            <div class="col-md-6 mb-4">
                <div class="nku-card h-100">
                    <div class="nku-card__header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-project-diagram me-2"></i>
                            Последние проекты
                        </h5>
                        <?= Html::a('Все проекты →', ['/project/index'], ['class' => 'nku-link']) ?>
                    </div>
                    <div class="nku-card__body">
                        <?php if (empty($recentProjects)): ?>
                            <div class="nku-empty">
                                <div class="nku-empty__icon">
                                    <i class="fas fa-folder-open"></i>
                                </div>
                                <div class="nku-empty__title">Нет проектов</div>
                                <div class="nku-empty__description">
                                    Проекты появятся здесь после их создания
                                </div>
                                <?php if (in_array($user->role, [User::ROLE_HEAD, User::ROLE_TOP_MANAGER, User::ROLE_ADMIN]) && $user->role !== User::ROLE_RECTOR): ?>
                                    <div class="nku-empty__action">
                                        <?= Html::a(
                                            '<i class="fas fa-plus me-2"></i>Создать проект',
                                            ['/project/create'],
                                            ['class' => 'nku-btn nku-btn--primary']
                                        ) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($recentProjects as $project): ?>
                                    <?php if (!$project || !$project->title): continue; endif; ?>
                                    <div class="list-group-item px-0 border-bottom">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <?= Html::a(
                                                    Html::encode($project->title), 
                                                    ['/project/view', 'id' => (string)$project->_id], 
                                                    ['class' => 'text-decoration-none fw-semibold']
                                                ) ?>
                                                <div class="d-flex align-items-center gap-2 mt-1">
                                                    <?php if ($project->created_at instanceof \MongoDB\BSON\UTCDateTime): ?>
                                                        <small class="text-muted">
                                                            <i class="far fa-calendar me-1"></i>
                                                            <?= date('d.m.Y', $project->created_at->toDateTime()->getTimestamp()) ?>
                                                        </small>
                                                    <?php endif; ?>
                                                    <?php if ($project->progress): ?>
                                                        <small class="text-muted">
                                                            <i class="fas fa-chart-line me-1"></i>
                                                            <?= $project->progress ?>%
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <span class="nku-badge nku-badge--status-<?= $project->status ?>">
                                                <?= $project->getStatusLabel() ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Мои задачи (executor) / Последние задачи (остальные) -->
        <div class="col-md-<?= $user->role !== User::ROLE_EXECUTOR ? '6' : '12' ?> mb-4">
            <div class="nku-card h-100">
                <div class="nku-card__header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-tasks me-2"></i>
                        <?= $user->role === User::ROLE_EXECUTOR ? 'Мои задачи' : 'Последние задачи' ?>
                    </h5>
                    <?php if (!empty($recentProjects)): ?>
                        <?= Html::a('Все проекты →', ['/project/index'], ['class' => 'nku-link']) ?>
                    <?php endif; ?>
                </div>
                <div class="nku-card__body">
                    <?php 
                    $tasksToShow = $user->role === User::ROLE_EXECUTOR ? ($myTasks ?? []) : ($recentTasks ?? []);
                    ?>
                    <?php if (empty($tasksToShow)): ?>
                        <div class="nku-empty">
                            <div class="nku-empty__icon">
                                <i class="fas fa-clipboard-list"></i>
                            </div>
                            <div class="nku-empty__title">
                                <?= $user->role === User::ROLE_EXECUTOR ? 'Нет назначенных задач' : 'Нет задач' ?>
                            </div>
                            <div class="nku-empty__description">
                                <?= $user->role === User::ROLE_EXECUTOR 
                                    ? 'Задачи появятся здесь после назначения' 
                                    : 'Задачи появятся после создания проектов' 
                                ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($tasksToShow as $task): ?>
                                <?php if (!$task || !$task->title): continue; endif; ?>
                                <?php 
                                // Пропускаем задачи с удаленными проектами
                                $project = $task->project;
                                if (!$project): continue; endif;
                                ?>
                                <div class="list-group-item px-0 border-bottom">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <?= Html::a(
                                                Html::encode($task->title), 
                                                ['/task/view', 'id' => (string)$task->_id], 
                                                ['class' => 'text-decoration-none fw-semibold']
                                            ) ?>
                                            <div class="d-flex align-items-center gap-2 mt-1">
                                                <?php 
                                                if ($project && $project->title): 
                                                ?>
                                                    <small class="text-muted">
                                                        <i class="fas fa-project-diagram me-1"></i>
                                                        <?= Html::encode($project->title) ?>
                                                    </small>
                                                <?php endif; ?>
                                                <span class="nku-badge nku-badge--priority-<?= $task->priority ?>">
                                                    <?= $task->getPriorityLabel() ?>
                                                </span>
                                            </div>
                                        </div>
                                        <span class="nku-badge nku-badge--status-<?= $task->status ?>">
                                            <?= $task->getStatusLabel() ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
