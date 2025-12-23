<?php

use yii\helpers\Html;
use app\models\Task;
use app\models\User;

/** @var yii\web\View $this */
/** @var app\models\Task $task */
/** @var app\models\User $user */
?>
<div class="task-card mb-2" data-task-id="<?= (string)$task->_id ?>">
    <div class="card shadow-sm">
        <div class="card-body p-2">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <h6 class="mb-0">
                    <?= Html::a(Html::encode($task->title), ['task/view', 'id' => (string)$task->_id], [
                        'class' => 'text-dark',
                        'style' => 'text-decoration: none;'
                    ]) ?>
                </h6>
                <span class="badge badge-<?= $task->getPriorityBadgeColor() ?> badge-sm">
                    <?= $task->getPriorityLabel() ?>
                </span>
            </div>
            
            <?php if ($task->description): ?>
                <p class="small text-muted mb-2" style="font-size: 0.85rem;">
                    <?= Html::encode(mb_substr($task->description, 0, 80)) ?>
                    <?= mb_strlen($task->description) > 80 ? '...' : '' ?>
                </p>
            <?php endif; ?>
            
            <div class="mb-2">
                <small class="text-muted">
                    <?php if ($task->executor_subdepartment_id): ?>
                        <i class="fas fa-users"></i> <?= Html::encode($task->getExecutorDisplayName()) ?>
                    <?php else: ?>
                        <i class="fas fa-user"></i> <?= Html::encode($task->getExecutorDisplayName()) ?>
                    <?php endif; ?>
                </small>
            </div>
            
            <?php if ($task->due_date instanceof \MongoDB\BSON\UTCDateTime): ?>
                <?php
                $dueDate = $task->due_date->toDateTime()->getTimestamp();
                $now = time();
                $daysLeft = floor(($dueDate - $now) / (24 * 60 * 60));
                $isOverdue = $daysLeft < 0;
                $isUrgent = $daysLeft >= 0 && $daysLeft <= 3;
                ?>
                <div class="mb-2">
                    <small class="<?= $isOverdue ? 'text-danger' : ($isUrgent ? 'text-warning' : 'text-muted') ?>">
                        <i class="fas fa-calendar"></i> 
                        <?= date('d.m.Y', $dueDate) ?>
                        <?php if ($isOverdue): ?>
                            <span class="badge badge-danger">Просрочено</span>
                        <?php elseif ($isUrgent): ?>
                            <span class="badge badge-warning">Скоро</span>
                        <?php endif; ?>
                    </small>
                </div>
            <?php endif; ?>
            
            <div class="progress mb-2" style="height: 5px;">
                <div class="progress-bar" 
                     role="progressbar" 
                     style="width: <?= $task->progress ?>%"
                     aria-valuenow="<?= $task->progress ?>" 
                     aria-valuemin="0" 
                     aria-valuemax="100">
                </div>
            </div>
            
            <div class="d-flex justify-content-between align-items-center">
                <?php
                $totalSubtasks = $task->getTotalSubtasksCount();
                if ($totalSubtasks > 0):
                ?>
                    <small class="text-muted">
                        <strong><?= $task->getProgressFormat() ?></strong>
                        <span class="ms-1">(<?= $task->progress ?>%)</span>
                    </small>
                <?php else: ?>
                    <small class="text-muted"><?= $task->progress ?>%</small>
                <?php endif; ?>
                <div class="btn-group btn-group-sm" role="group">
                    <?php if ($user->role !== User::ROLE_RECTOR): // Ректор может только просматривать ?>
                        <?php if ($user->role === User::ROLE_EXECUTOR && $task->isAssignedToUser($user)): ?>
                            <!-- Исполнитель может менять статус -->
                            <?php if ($task->status !== Task::STATUS_IN_PROGRESS): ?>
                                <?= Html::a('В работу', ['task/change-status', 'id' => (string)$task->_id, 'status' => Task::STATUS_IN_PROGRESS], [
                                    'class' => 'btn btn-sm btn-primary change-status',
                                    'data-status' => Task::STATUS_IN_PROGRESS,
                                ]) ?>
                            <?php endif; ?>
                            <?php if ($task->status === Task::STATUS_IN_PROGRESS): ?>
                                <?= Html::a('На проверку', ['task/change-status', 'id' => (string)$task->_id, 'status' => Task::STATUS_REVIEW], [
                                    'class' => 'btn btn-sm btn-warning change-status',
                                    'data-status' => Task::STATUS_REVIEW,
                                ]) ?>
                            <?php endif; ?>
                        <?php elseif (in_array($user->role, [User::ROLE_MANAGER, User::ROLE_TOP_MANAGER, User::ROLE_HEAD])): ?>
                            <!-- Менеджер, топ-менеджер и ректор могут отправить на доработку -->
                            <?php if ($task->status === Task::STATUS_REVIEW): ?>
                                <?= Html::a('На доработку', ['task/change-status', 'id' => (string)$task->_id, 'status' => Task::STATUS_IN_PROGRESS], [
                                    'class' => 'btn btn-sm btn-secondary change-status',
                                    'data-status' => Task::STATUS_IN_PROGRESS,
                                ]) ?>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php 
                        // Менеджер, топ-менеджер, ректор и админ могут редактировать задачи
                        $canEditTask = in_array($user->role, [User::ROLE_MANAGER, User::ROLE_TOP_MANAGER, User::ROLE_HEAD, User::ROLE_ADMIN]);
                        if ($canEditTask): ?>
                            <?= Html::a('Редактировать', ['task/update', 'id' => (string)$task->_id], ['class' => 'btn btn-sm btn-info']) ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.task-card .card {
    border-left: 3px solid;
    transition: transform 0.2s, box-shadow 0.2s;
    cursor: pointer;
}

.task-card .card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.15) !important;
}

.task-card[data-priority="critical"] .card {
    border-left-color: #dc3545;
}

.task-card[data-priority="high"] .card {
    border-left-color: #ffc107;
}

.task-card[data-priority="medium"] .card {
    border-left-color: #17a2b8;
}

.task-card[data-priority="low"] .card {
    border-left-color: #6c757d;
}

.kanban-column {
    min-height: 400px;
    background-color: #f8f9fa;
    border-radius: 0.25rem;
}

.kanban-header {
    font-weight: bold;
    margin-bottom: 0;
}

.kanban-body {
    min-height: 350px;
    padding: 10px;
    background-color: #fff;
    border-radius: 0 0 0.25rem 0.25rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Обработка изменения статуса через AJAX
    document.querySelectorAll('.change-status').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (!confirm('Изменить статус задачи?')) {
                return;
            }
            
            var url = this.href;
            var taskCard = this.closest('.task-card');
            
            fetch(url, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Ошибка при изменении статуса');
                }
            })
            .catch(error => {
                alert('Ошибка при изменении статуса');
            });
        });
    });
});
</script>

