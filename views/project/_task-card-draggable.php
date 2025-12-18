<?php

use yii\helpers\Html;
use app\models\Task;
use app\models\User;

/** @var yii\web\View $this */
/** @var app\models\Task $task */
/** @var app\models\User $user */
?>
<div class="task-card-draggable" 
     data-task-id="<?= (string)$task->_id ?>" 
     data-priority="<?= $task->priority ?>"
     draggable="true">
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
                            <span class="badge badge-danger badge-sm">Просрочено</span>
                        <?php elseif ($isUrgent): ?>
                            <span class="badge badge-warning badge-sm">Скоро</span>
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
                <small class="text-muted"><?= $task->progress ?>%</small>
                <div class="btn-group btn-group-sm" role="group">
                    <?= Html::a('Просмотр', ['task/view', 'id' => (string)$task->_id], ['class' => 'btn btn-sm btn-info']) ?>
                    <?php 
                    // Менеджер, топ-менеджер, ректор и админ могут редактировать задачи
                    $canEditTask = in_array($user->role, [User::ROLE_MANAGER, User::ROLE_TOP_MANAGER, User::ROLE_RECTOR, User::ROLE_ADMIN]);
                    if ($canEditTask): ?>
                        <?= Html::a('Редактировать', ['task/update', 'id' => (string)$task->_id], ['class' => 'btn btn-sm btn-secondary']) ?>
                    <?php elseif ($user->role === User::ROLE_EXECUTOR && $task->isAssignedToUser($user)): ?>
                        <?= Html::a('Редактировать', ['task/update', 'id' => (string)$task->_id], ['class' => 'btn btn-sm btn-secondary']) ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>


