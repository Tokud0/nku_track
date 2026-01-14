<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\models\Project;
use app\models\Task;
use app\models\User;
use app\models\GlobalProjectRole;

/** @var yii\web\View $this */
/** @var app\models\Project $model */
/** @var app\models\Task[] $tasks */
/** @var string|null $userGlobalRole */

$this->title = 'Список задач - ' . $model->title;
$this->params['breadcrumbs'][] = ['label' => 'Глобальный проект', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->title, 'url' => ['view', 'id' => (string)$model->_id]];
$this->params['breadcrumbs'][] = 'Список задач';

$user = Yii::$app->user->identity;
?>

<div class="tasks-list">
    <!-- Header -->
    <div class="nku-card mb-4">
        <div class="nku-card__body">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="flex-grow-1">
                    <h1 class="mb-2"><?= Html::encode($this->title) ?></h1>
                    <p class="text-muted mb-0">
                        <i class="fas fa-project-diagram me-2"></i>
                        <?= Html::a(
                            Html::encode($model->title),
                            ['view', 'id' => (string)$model->_id],
                            ['class' => 'text-decoration-none']
                        ) ?>
                    </p>
                </div>
                <div class="text-end">
                    <div class="d-flex gap-2">
                        <?= Html::a(
                            '<i class="fas fa-arrow-left me-2"></i>К проекту',
                            ['view', 'id' => (string)$model->_id],
                            ['class' => 'nku-btn nku-btn--secondary']
                        ) ?>
                        <?= Html::a(
                            '<i class="fas fa-columns me-2"></i>Канбан-доска',
                            ['kanban', 'id' => (string)$model->_id],
                            ['class' => 'nku-btn nku-btn--primary']
                        ) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tasks Table -->
    <div class="nku-card">
        <div class="nku-card__header">
            <h5 class="mb-0">
                <i class="fas fa-tasks me-2"></i>
                Все задачи проекта
                <span class="badge bg-primary ms-2"><?= count($tasks) ?></span>
            </h5>
        </div>
        <div class="nku-card__body">
            <?php if (count($tasks) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th style="width: 40%;">Название задачи</th>
                                <th style="width: 15%;">Статус</th>
                                <th style="width: 15%;">Прогресс</th>
                                <th style="width: 15%;">Приоритет</th>
                                <th style="width: 15%;">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tasks as $task): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold mb-1">
                                            <?= Html::encode($task->title) ?>
                                        </div>
                                        <?php if ($task->description): ?>
                                            <small class="text-muted">
                                                <?= Html::encode(mb_substr($task->description, 0, 100)) ?>
                                                <?= mb_strlen($task->description) > 100 ? '...' : '' ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="nku-badge nku-badge--status-<?= $task->status ?>">
                                            <i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i>
                                            <?= $task->getStatusLabel() ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php $progress = $task->calculateProgress(); ?>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1" style="height: 20px;">
                                                <div class="progress-bar 
                                                    <?= $progress >= 100 ? 'bg-success' : ($progress >= 50 ? 'bg-primary' : 'bg-warning') ?>" 
                                                     role="progressbar" 
                                                     style="width: <?= $progress ?>%"
                                                     aria-valuenow="<?= $progress ?>" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="100">
                                                    <small><?= $progress ?>%</small>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="nku-badge nku-badge--priority-<?= $task->priority ?>">
                                            <?= $task->getPriorityLabel() ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= Html::a(
                                            '<i class="fas fa-eye me-2"></i>Просмотр',
                                            ['task/view', 'id' => (string)$task->_id],
                                            ['class' => 'nku-btn nku-btn--sm nku-btn--info']
                                        ) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="nku-empty">
                    <div class="nku-empty__icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="nku-empty__title">Задачи не найдены</div>
                    <div class="nku-empty__description">
                        В этом проекте пока нет задач
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.tasks-list .table {
    margin-bottom: 0;
}

.tasks-list .table thead th {
    border-bottom: 2px solid var(--nku-color-border);
    font-weight: 600;
    color: var(--nku-color-text-primary);
    background-color: var(--nku-color-bg-secondary);
}

.tasks-list .table tbody tr {
    transition: background-color 0.2s ease;
}

.tasks-list .table tbody tr:hover {
    background-color: var(--nku-color-bg-secondary);
}

.tasks-list .progress {
    min-width: 80px;
}

.tasks-list .progress-bar {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 600;
    color: #fff;
}

.tasks-list .nku-badge {
    white-space: nowrap;
}
</style>
