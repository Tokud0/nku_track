<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\models\Task;
use app\models\User;

/** @var yii\web\View $this */
/** @var app\models\Project $model */
/** @var app\models\Task[] $tasks */

$this->title = 'Архив задач: ' . $model->title;
$this->params['breadcrumbs'][] = ['label' => 'Проекты', 'url' => ['project/index']];
$this->params['breadcrumbs'][] = ['label' => $model->title, 'url' => ['project/view', 'id' => (string)$model->_id]];
$this->params['breadcrumbs'][] = 'Архив задач';

$user = Yii::$app->user->identity;
$canUnarchive = in_array($user->role, [User::ROLE_MANAGER, User::ROLE_TOP_MANAGER, User::ROLE_HEAD, User::ROLE_ADMIN]);
?>

<div class="project-archive">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1">
                <i class="fas fa-archive me-2"></i>
                Архив задач
            </h1>
            <p class="text-muted mb-0">
                Проект: <?= Html::encode($model->title) ?>
            </p>
        </div>
        <div>
            <?= Html::a(
                '<i class="fas fa-arrow-left me-2"></i>К проекту',
                ['project/view', 'id' => (string)$model->_id],
                ['class' => 'nku-btn nku-btn--secondary']
            ) ?>
            <?= Html::a(
                '<i class="fas fa-columns me-2"></i>Канбан-доска',
                ['project/kanban', 'id' => (string)$model->_id],
                ['class' => 'nku-btn nku-btn--primary']
            ) ?>
        </div>
    </div>

    <!-- Archive Tasks List -->
    <div class="nku-card">
        <div class="nku-card__header">
            <h5 class="mb-0">
                <i class="fas fa-list me-2"></i>
                Архивные задачи
                <span class="nku-badge nku-badge--secondary ms-2"><?= count($tasks) ?></span>
            </h5>
        </div>
        <div class="nku-card__body">
            <?php if (empty($tasks)): ?>
                <div class="nku-empty">
                    <div class="nku-empty__icon">
                        <i class="fas fa-archive"></i>
                    </div>
                    <div class="nku-empty__title">Архив пуст</div>
                    <div class="nku-empty__description">
                        Архивные задачи появятся здесь после архивации выполненных задач
                    </div>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th style="width: 40%;">Название</th>
                                <th style="width: 15%;">Статус</th>
                                <th style="width: 15%;">Приоритет</th>
                                <th style="width: 15%;">Исполнитель</th>
                                <th style="width: 15%;">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tasks as $task): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div>
                                                <?= Html::a(
                                                    Html::encode($task->title),
                                                    ['task/view', 'id' => (string)$task->_id],
                                                    ['class' => 'text-decoration-none fw-semibold']
                                                ) ?>
                                                <?php if ($task->description): ?>
                                                    <div class="text-muted small mt-1">
                                                        <?= Html::encode(mb_substr($task->description, 0, 100)) ?>
                                                        <?= mb_strlen($task->description) > 100 ? '...' : '' ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="nku-badge nku-badge--status-<?= $task->status ?>">
                                            <?= $task->getStatusLabel() ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="nku-badge nku-badge--priority-<?= $task->priority ?>">
                                            <?= $task->getPriorityLabel() ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?= Html::encode($task->getExecutorDisplayName()) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <?= Html::a(
                                                '<i class="fas fa-eye"></i>',
                                                ['task/view', 'id' => (string)$task->_id],
                                                [
                                                    'class' => 'btn btn-sm btn-outline-primary',
                                                    'title' => 'Просмотр',
                                                ]
                                            ) ?>
                                            <?php if ($canUnarchive): ?>
                                                <?= Html::a(
                                                    '<i class="fas fa-box-open"></i>',
                                                    ['task/unarchive', 'id' => (string)$task->_id],
                                                    [
                                                        'class' => 'btn btn-sm btn-outline-warning',
                                                        'title' => 'Разархивировать',
                                                        'data' => [
                                                            'confirm' => 'Вы уверены, что хотите разархивировать эту задачу?',
                                                            'method' => 'post',
                                                        ],
                                                    ]
                                                ) ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.project-archive .table th {
    border-bottom: 2px solid var(--nku-color-border);
    font-weight: 600;
    color: var(--nku-color-text-primary);
}

.project-archive .table td {
    vertical-align: middle;
    border-bottom: 1px solid var(--nku-color-border);
}

.project-archive .table tbody tr:hover {
    background-color: var(--nku-color-bg-hover);
}
</style>


