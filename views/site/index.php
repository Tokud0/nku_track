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

if (Yii::$app->user->isGuest) {
    ?>
    <div class="site-index">
        <div class="jumbotron text-center bg-light py-5 mb-4 rounded">
            <h1 class="display-4">Добро пожаловать!</h1>
            <p class="lead">Система управления проектами и задачами</p>
            <p>
                <?= Html::a('Войти', ['/site/login'], ['class' => 'btn btn-lg btn-primary']) ?>
            </p>
        </div>
    </div>
    <?php
    return;
}

$user = Yii::$app->user->identity;
?>

<div class="site-index">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="mb-0">Добро пожаловать, <?= Html::encode($user->fio) ?>!</h1>
            <p class="text-muted"><?= Html::encode($user->email) ?></p>
        </div>
    </div>

    <!-- Быстрые действия -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">Быстрые действия</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php if (in_array($user->role, [User::ROLE_RECTOR, User::ROLE_ADMIN])): ?>
                            <div class="col-md-3 col-sm-6 mb-2">
                                <?= Html::a('<i class="fas fa-plus-circle"></i> Создать проект', ['/project/create'], [
                                    'class' => 'btn btn-primary btn-block'
                                ]) ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (in_array($user->role, [User::ROLE_MANAGER, User::ROLE_TOP_MANAGER, User::ROLE_RECTOR, User::ROLE_ADMIN])): ?>
                            <?php if (!empty($recentProjects)): ?>
                                <div class="col-md-3 col-sm-6 mb-2">
                                    <?= Html::a('<i class="fas fa-plus"></i> Создать задачу', ['/task/create', 'project_id' => (string)$recentProjects[0]->_id], [
                                        'class' => 'btn btn-success btn-block'
                                    ]) ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <div class="col-md-3 col-sm-6 mb-2">
                            <?= Html::a('<i class="fas fa-list"></i> Все проекты', ['/project/index'], [
                                'class' => 'btn btn-info btn-block'
                            ]) ?>
                        </div>
                        
                        <?php if ($user->role === User::ROLE_ADMIN): ?>
                            <div class="col-md-3 col-sm-6 mb-2">
                                <?= Html::a('<i class="fas fa-cog"></i> Админка', ['/admin'], [
                                    'class' => 'btn btn-secondary btn-block'
                                ]) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Последние проекты -->
        <?php if (!empty($recentProjects) && $user->role !== User::ROLE_EXECUTOR): ?>
            <div class="col-md-6 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Последние проекты</h5>
                        <?= Html::a('Все проекты', ['/project/index'], ['class' => 'btn btn-sm btn-link']) ?>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentProjects)): ?>
                            <p class="text-muted mb-0">Нет проектов</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($recentProjects as $project): ?>
                                    <div class="list-group-item px-0 border-0">
                                        <?= Html::a(Html::encode($project->title), ['/project/view', 'id' => (string)$project->_id], [
                                            'class' => 'text-decoration-none'
                                        ]) ?>
                                        <div class="d-flex justify-content-between align-items-center mt-1">
                                            <small class="text-muted">
                                                <?= date('d.m.Y', $project->created_at->toDateTime()->getTimestamp()) ?>
                                            </small>
                                            <span class="badge badge-<?= [
                                                Project::STATUS_DRAFT => 'secondary',
                                                Project::STATUS_ACTIVE => 'success',
                                                Project::STATUS_REVIEW => 'warning',
                                                Project::STATUS_FINISHED => 'info',
                                                Project::STATUS_FROZEN => 'danger',
                                            ][$project->status] ?? 'secondary' ?>">
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

        <!-- Мои задачи / Последние задачи -->
        <div class="col-md-<?= !empty($recentProjects) && $user->role !== User::ROLE_EXECUTOR ? '6' : '12' ?> mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><?= $user->role === User::ROLE_EXECUTOR ? 'Мои задачи' : 'Последние задачи' ?></h5>
                    <?php if (!empty($recentProjects)): ?>
                        <?= Html::a('Все задачи', ['/project/index'], ['class' => 'btn btn-sm btn-link']) ?>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php 
                    $tasksToShow = $user->role === User::ROLE_EXECUTOR ? ($myTasks ?? []) : ($recentTasks ?? []);
                    ?>
                    <?php if (empty($tasksToShow)): ?>
                        <p class="text-muted mb-0">Нет задач</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($tasksToShow as $task): ?>
                                <div class="list-group-item px-0 border-0">
                                    <?= Html::a(Html::encode($task->title), ['/task/view', 'id' => (string)$task->_id], [
                                        'class' => 'text-decoration-none'
                                    ]) ?>
                                    <div class="d-flex justify-content-between align-items-center mt-1">
                                        <small class="text-muted">
                                            Проект: <?= Html::encode($task->project->title) ?>
                                        </small>
                                        <span class="badge badge-<?= [
                                            Task::STATUS_TODO => 'secondary',
                                            Task::STATUS_IN_PROGRESS => 'primary',
                                            Task::STATUS_REVIEW => 'warning',
                                            Task::STATUS_DONE => 'success',
                                            Task::STATUS_CANCELED => 'danger',
                                        ][$task->status] ?? 'secondary' ?>">
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

<style>
.site-index .card {
    transition: transform 0.2s, box-shadow 0.2s;
}

.site-index .card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.site-index .list-group-item {
    padding: 0.75rem 0;
}

.site-index .list-group-item:hover {
    background-color: #f8f9fa;
    border-radius: 0.25rem;
}
</style>
