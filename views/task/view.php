<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\models\Task;
use app\models\User;
use app\models\Comment;

/** @var yii\web\View $this */
/** @var app\models\Task $model */

$this->title = $model->title;

// Проверяем, является ли проект глобальным
$isGlobalProject = $model->project && $model->project->isGlobal();

if ($isGlobalProject) {
    $this->params['breadcrumbs'][] = ['label' => 'Глобальный проект', 'url' => ['global-project/index']];
    $this->params['breadcrumbs'][] = ['label' => $model->project->title, 'url' => ['global-project/view', 'id' => (string)$model->project_id]];
} else {
    $this->params['breadcrumbs'][] = ['label' => 'Проекты', 'url' => ['project/index']];
    $this->params['breadcrumbs'][] = ['label' => $model->project->title, 'url' => ['project/view', 'id' => (string)$model->project_id]];
}
$this->params['breadcrumbs'][] = $this->title;

$user = Yii::$app->user->identity;
$isExecutor = $user->role === User::ROLE_EXECUTOR && $model->isAssignedToUser($user);
$canEditTask = in_array($user->role, [User::ROLE_MANAGER, User::ROLE_TOP_MANAGER, User::ROLE_HEAD, User::ROLE_ADMIN]) || $isExecutor;
$canComment = $user->role !== User::ROLE_RECTOR; // Ректор не может комментировать

// Получаем комментарии
$comments = Comment::find()
    ->where(['task_id' => $model->_id])
    ->orderBy(['created_at' => SORT_ASC])
    ->all();
?>

<div class="task-view">
    <!-- Task Header -->
    <div class="nku-card mb-4">
        <div class="nku-card__body">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <h1 class="mb-0"><?= Html::encode($this->title) ?></h1>
                        <div class="d-flex gap-2">
                            <span class="nku-badge nku-badge--lg nku-badge--status-<?= $model->status ?>">
                                <i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i>
                                <?= $model->getStatusLabel() ?>
                            </span>
                            <span class="nku-badge nku-badge--lg nku-badge--priority-<?= $model->priority ?>">
                                <?= $model->getPriorityLabel() ?>
                            </span>
                        </div>
                    </div>
                    <p class="text-muted mb-0">
                        <i class="fas fa-project-diagram me-2"></i>
                        <?php if ($model->project): ?>
                            <?= Html::a(
                                Html::encode($model->project->title),
                                $isGlobalProject ? ['global-project/view', 'id' => (string)$model->project_id] : ['project/view', 'id' => (string)$model->project_id],
                                ['class' => 'text-decoration-none']
                            ) ?>
                        <?php else: ?>
                            <span class="text-danger">Проект удален</span>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="text-end">
                    <?php if ($model->project): ?>
                        <div class="d-flex gap-2 mb-2">
                            <?= Html::a(
                                '<i class="fas fa-columns me-2"></i>К доске',
                                $isGlobalProject ? ['global-project/kanban', 'id' => (string)$model->project_id] : ['project/kanban', 'id' => (string)$model->project_id],
                                ['class' => 'nku-btn nku-btn--info nku-btn--outline']
                            ) ?>
                            <?= Html::a(
                                '<i class="fas fa-arrow-left me-2"></i>К проекту',
                                $isGlobalProject ? ['global-project/view', 'id' => (string)$model->project_id] : ['project/view', 'id' => (string)$model->project_id],
                                ['class' => 'nku-btn nku-btn--secondary']
                            ) ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($canEditTask): ?>
                        <div class="d-flex gap-2">
                            <?= Html::a(
                                '<i class="fas fa-edit me-2"></i>Редактировать',
                                ['update', 'id' => (string)$model->_id],
                                ['class' => 'nku-btn nku-btn--primary']
                            ) ?>
                            <?php if (in_array($user->role, [User::ROLE_MANAGER, User::ROLE_TOP_MANAGER, User::ROLE_HEAD, User::ROLE_ADMIN])): ?>
                                <?php if ($model->is_archived): ?>
                                    <?= Html::a(
                                        '<i class="fas fa-box-open me-2"></i>Разархивировать',
                                        ['unarchive', 'id' => (string)$model->_id],
                                        [
                                            'class' => 'nku-btn nku-btn--success',
                                            'data' => [
                                                'confirm' => 'Вы уверены, что хотите разархивировать эту задачу?',
                                                'method' => 'post',
                                            ],
                                        ]
                                    ) ?>
                                <?php elseif ($model->status === Task::STATUS_DONE): ?>
                                    <?= Html::a(
                                        '<i class="fas fa-archive me-2"></i>В архив',
                                        ['archive', 'id' => (string)$model->_id],
                                        [
                                            'class' => 'nku-btn nku-btn--warning',
                                            'data' => [
                                                'confirm' => 'Вы уверены, что хотите архивировать эту задачу?',
                                                'method' => 'post',
                                            ],
                                        ]
                                    ) ?>
                                <?php endif; ?>
                                <?= Html::a(
                                    '<i class="fas fa-trash me-2"></i>Удалить',
                                    ['delete', 'id' => (string)$model->_id],
                                    [
                                        'class' => 'nku-btn nku-btn--danger',
                                        'data' => [
                                            'confirm' => 'Вы уверены, что хотите удалить эту задачу?',
                                            'method' => 'post',
                                        ],
                                    ]
                                ) ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Progress & Deadlines -->
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label fw-semibold mb-2">
                        <i class="fas fa-tasks me-2"></i>
                        Прогресс выполнения
                    </label>
                    <?php
                    $totalSubtasks = $model->getTotalSubtasksCount();
                    $completedSubtasks = $model->getCompletedSubtasksCount();
                    $progressFormat = $model->getProgressFormat();
                    ?>
                    <?php if ($totalSubtasks > 0): ?>
                        <div class="mb-2" id="progress-format-display">
                            <span class="fw-bold fs-5" id="progress-format-text"><?= $progressFormat ?></span>
                            <small class="text-muted ms-2" id="progress-percent-text">(<?= $model->progress ?>%)</small>
                        </div>
                    <?php else: ?>
                        <div class="mb-2" id="progress-format-display">
                            <span class="fw-bold fs-5" id="progress-format-text"><?= $model->progress ?>%</span>
                        </div>
                    <?php endif; ?>
                    <div class="nku-progress nku-progress--lg">
                        <div class="nku-progress__bar nku-progress__bar--<?= $model->progress >= 100 ? 'success' : ($model->progress >= 50 ? 'primary' : 'warning') ?>" 
                             id="progress-bar"
                             style="width: <?= $model->progress ?>%">
                            <span class="nku-progress__label" id="progress-label"><?= $model->progress ?>%</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold mb-2">
                        <i class="fas fa-clock me-2"></i>
                        Срок выполнения
                    </label>
                    <div>
                        <?php if ($model->due_date instanceof \MongoDB\BSON\UTCDateTime): ?>
                            <?php
                            $dueDate = $model->due_date->toDateTime()->getTimestamp();
                            $now = time();
                            $daysLeft = floor(($dueDate - $now) / (24 * 60 * 60));
                            $isOverdue = $daysLeft < 0;
                            $isUrgent = $daysLeft <= 3 && !$isOverdue;
                            ?>
                            <div class="p-2 rounded <?= $isOverdue ? 'bg-danger bg-opacity-10' : ($isUrgent ? 'bg-warning bg-opacity-10' : 'bg-light') ?>">
                                <div class="fw-bold <?= $isOverdue ? 'text-danger' : ($isUrgent ? 'text-warning' : 'text-success') ?>">
                                    <?= date('d.m.Y', $dueDate) ?>
                                </div>
                                <small class="text-muted">
                                    <?php if ($isOverdue): ?>
                                        Просрочено на <?= abs($daysLeft) ?> дн.
                                    <?php elseif ($isUrgent): ?>
                                        Осталось <?= $daysLeft ?> дн.
                                    <?php else: ?>
                                        Осталось <?= $daysLeft ?> дн.
                                    <?php endif; ?>
                                </small>
                            </div>
                        <?php else: ?>
                            <span class="nku-badge nku-badge--secondary">Не установлен</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Left Column: Details & Assignment -->
        <div class="col-lg-8 mb-4">
            <!-- Task Details -->
            <div class="nku-card mb-4">
                <div class="nku-card__header">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Описание задачи
                    </h5>
                </div>
                <div class="nku-card__body">
                    <?php if ($model->description): ?>
                        <div class="task-description">
                            <?= nl2br(Html::encode($model->description)) ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">Описание не указано</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Subtasks (To-Do List) -->
            <?php
            $subtasks = is_array($model->subtasks) ? $model->subtasks : [];
            if (!empty($subtasks) || $canEditTask):
            ?>
            <div class="nku-card mb-4">
                <div class="nku-card__header">
                    <h5 class="mb-0">
                        <i class="fas fa-list-check me-2"></i>
                        Подзадачи (To-Do лист)
                        <?php if (!empty($subtasks)): ?>
                            <span class="nku-badge nku-badge--secondary ms-2" id="subtasks-progress-badge">
                                <?= $model->getProgressFormat() ?>
                            </span>
                        <?php endif; ?>
                    </h5>
                </div>
                <div class="nku-card__body">
                    <?php if (empty($subtasks)): ?>
                        <div class="nku-empty nku-empty--sm">
                            <div class="nku-empty__icon">
                                <i class="far fa-list"></i>
                            </div>
                            <div class="nku-empty__title">Подзадач пока нет</div>
                            <div class="nku-empty__description">
                                Добавьте подзадачи при редактировании задачи
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="subtasks-list">
                            <?php foreach ($subtasks as $index => $subtask): ?>
                                <?php
                                $subtaskText = isset($subtask['text']) ? $subtask['text'] : '';
                                $isCompleted = isset($subtask['completed']) && $subtask['completed'] === true;
                                ?>
                                <div class="subtask-item d-flex align-items-center mb-2 p-2 rounded <?= $isCompleted ? 'bg-light' : '' ?>" 
                                     data-subtask-index="<?= $index ?>">
                                    <div class="form-check me-3">
                                        <input class="form-check-input subtask-checkbox" 
                                               type="checkbox" 
                                               <?= $isCompleted ? 'checked' : '' ?>
                                               data-task-id="<?= (string)$model->_id ?>"
                                               data-subtask-index="<?= $index ?>"
                                               <?= !$canEditTask ? 'disabled' : '' ?>
                                               style="width: 1.25rem; height: 1.25rem; cursor: pointer;">
                                    </div>
                                    <div class="flex-grow-1 <?= $isCompleted ? 'text-decoration-line-through text-muted' : '' ?>">
                                        <?= Html::encode($subtaskText) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Assignment Info -->
            <div class="nku-card mb-4">
                <div class="nku-card__header">
                    <h5 class="mb-0">
                        <i class="fas fa-user-tag me-2"></i>
                        Назначение
                    </h5>
                </div>
                <div class="nku-card__body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="text-muted mb-1">Исполнитель</label>
                            <div class="d-flex align-items-center">
                                <i class="fas fa-user-circle text-primary me-2" style="font-size: 1.5rem;"></i>
                                <div>
                                    <div class="fw-semibold"><?= Html::encode($model->getExecutorDisplayName()) ?></div>
                                    <?php 
                                    $assignedUsers = $model->getAssignedUsers();
                                    if (!empty($assignedUsers) && count($assignedUsers) === 1): 
                                        $executorUser = $assignedUsers[0];
                                    ?>
                                        <small class="text-muted"><?= Html::encode($executorUser->email ?? '') ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php if ($isGlobalProject && $model->responsible_user_id): ?>
                            <?php $responsibleUser = $model->responsibleUser; ?>
                            <div class="col-md-6 mb-3">
                                <label class="text-muted mb-1">Ответственный</label>
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-user-tie text-info me-2" style="font-size: 1.5rem;"></i>
                                    <div>
                                        <div class="fw-semibold"><?= Html::encode($responsibleUser ? ($responsibleUser->fio . ' (' . $responsibleUser->email . ')') : 'Не указан') ?></div>
                                        <?php if ($responsibleUser): ?>
                                            <small class="text-muted">Глобальный менеджер</small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted mb-1">Создатель</label>
                            <div class="d-flex align-items-center">
                                <i class="fas fa-user text-secondary me-2" style="font-size: 1.5rem;"></i>
                                <div>
                                    <div class="fw-semibold"><?= $model->creator ? Html::encode($model->creator->fio) : 'Не указан' ?></div>
                                    <?php if ($model->creator): ?>
                                        <small class="text-muted"><?= Html::encode($model->creator->email ?? '') ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Comments Section -->
            <div class="nku-card">
                <div class="nku-card__header">
                    <h5 class="mb-0">
                        <i class="fas fa-comments me-2"></i>
                        Комментарии
                        <span class="nku-badge nku-badge--secondary ms-2"><?= count($comments) ?></span>
                    </h5>
                </div>
                <div class="nku-card__body">
                    <!-- Comments List -->
                    <?php if (empty($comments)): ?>
                        <div class="nku-empty nku-empty--sm">
                            <div class="nku-empty__icon">
                                <i class="far fa-comments"></i>
                            </div>
                            <div class="nku-empty__title">Комментариев пока нет</div>
                            <div class="nku-empty__description">
                                Будьте первым, кто оставит комментарий к этой задаче
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="nku-comments-list">
                            <?php foreach ($comments as $comment): ?>
                                <div class="nku-comment">
                                    <div class="nku-comment__avatar">
                                        <i class="fas fa-user-circle"></i>
                                    </div>
                                    <div class="nku-comment__content">
                                        <div class="nku-comment__header">
                                            <span class="nku-comment__author"><?= Html::encode($comment->author->fio) ?></span>
                                            <span class="nku-comment__time">
                                                <?php if ($comment->created_at instanceof \MongoDB\BSON\UTCDateTime): ?>
                                                    <i class="far fa-clock me-1"></i>
                                                    <?= date('d.m.Y H:i', $comment->created_at->toDateTime()->getTimestamp()) ?>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                        <div class="nku-comment__text">
                                            <?= nl2br(Html::encode($comment->text)) ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Comment Composer -->
                    <div class="nku-comment-composer mt-4">
                        <?php if ($canComment): ?>
                            <?php $commentForm = \yii\widgets\ActiveForm::begin([
                                'action' => ['comment/create'],
                                'method' => 'post',
                                'options' => ['class' => 'nku-comment-form'],
                            ]); ?>
                            
                            <?= Html::hiddenInput('Comment[task_id]', (string)$model->_id) ?>
                            
                            <div class="form-group">
                                <label class="form-label fw-semibold mb-2">
                                    <i class="fas fa-pen me-2"></i>
                                    Добавить комментарий
                                </label>
                                <textarea name="Comment[text]" 
                                          class="form-control" 
                                          rows="4" 
                                          placeholder="Напишите ваш комментарий..."
                                          required></textarea>
                            </div>
                            
                            <div class="d-flex justify-content-end">
                                <?= Html::submitButton(
                                    '<i class="fas fa-paper-plane me-2"></i>Отправить комментарий',
                                    ['class' => 'nku-btn nku-btn--primary']
                                ) ?>
                            </div>
                            
                            <?php \yii\widgets\ActiveForm::end(); ?>
                        <?php else: ?>
                            <!-- Rector restriction message -->
                            <div class="alert alert-warning d-flex align-items-center mb-0" role="alert">
                                <i class="fas fa-lock me-3" style="font-size: 1.5rem;"></i>
                                <div>
                                    <strong>Комментарии недоступны для роли Руководитель</strong>
                                    <div class="small">В соответствии с политикой доступа, руководители не могут оставлять комментарии к задачам.</div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Timeline & Meta -->
        <div class="col-lg-4">
            <!-- Timeline -->
            <div class="nku-card mb-4">
                <div class="nku-card__header">
                    <h5 class="mb-0">
                        <i class="fas fa-history me-2"></i>
                        Временная шкала
                    </h5>
                </div>
                <div class="nku-card__body">
                    <div class="nku-timeline">
                        <?php if ($model->created_at instanceof \MongoDB\BSON\UTCDateTime): ?>
                            <div class="nku-timeline__item">
                                <div class="nku-timeline__icon nku-timeline__icon--success">
                                    <i class="fas fa-plus"></i>
                                </div>
                                <div class="nku-timeline__content">
                                    <div class="nku-timeline__title">Создана</div>
                                    <div class="nku-timeline__time">
                                        <?= date('d.m.Y H:i', $model->created_at->toDateTime()->getTimestamp()) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($model->start_date instanceof \MongoDB\BSON\UTCDateTime): ?>
                            <div class="nku-timeline__item">
                                <div class="nku-timeline__icon nku-timeline__icon--primary">
                                    <i class="fas fa-play"></i>
                                </div>
                                <div class="nku-timeline__content">
                                    <div class="nku-timeline__title">Дата начала</div>
                                    <div class="nku-timeline__time">
                                        <?= date('d.m.Y', $model->start_date->toDateTime()->getTimestamp()) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($model->due_date instanceof \MongoDB\BSON\UTCDateTime): ?>
                            <?php
                            $dueDate = $model->due_date->toDateTime()->getTimestamp();
                            $isOverdue = $dueDate < time();
                            ?>
                            <div class="nku-timeline__item">
                                <div class="nku-timeline__icon nku-timeline__icon--<?= $isOverdue ? 'danger' : 'warning' ?>">
                                    <i class="fas fa-flag-checkered"></i>
                                </div>
                                <div class="nku-timeline__content">
                                    <div class="nku-timeline__title">
                                        Срок выполнения
                                        <?php if ($isOverdue): ?>
                                            <span class="nku-badge nku-badge--xs nku-badge--danger ms-1">Просрочен</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="nku-timeline__time">
                                        <?= date('d.m.Y', $dueDate) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($model->updated_at instanceof \MongoDB\BSON\UTCDateTime): ?>
                            <div class="nku-timeline__item">
                                <div class="nku-timeline__icon nku-timeline__icon--info">
                                    <i class="fas fa-edit"></i>
                                </div>
                                <div class="nku-timeline__content">
                                    <div class="nku-timeline__title">Обновлена</div>
                                    <div class="nku-timeline__time">
                                        <?= date('d.m.Y H:i', $model->updated_at->toDateTime()->getTimestamp()) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($model->status === Task::STATUS_DONE): ?>
                            <div class="nku-timeline__item">
                                <div class="nku-timeline__icon nku-timeline__icon--success">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="nku-timeline__content">
                                    <div class="nku-timeline__title">Завершена</div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!$model->created_at && !$model->start_date && !$model->due_date): ?>
                        <div class="nku-empty nku-empty--sm">
                            <div class="nku-empty__icon">
                                <i class="far fa-calendar"></i>
                            </div>
                            <div class="nku-empty__title">История недоступна</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Meta Info -->
            <div class="nku-card">
                <div class="nku-card__header">
                    <h5 class="mb-0">
                        <i class="fas fa-info me-2"></i>
                        Информация
                    </h5>
                </div>
                <div class="nku-card__body">
                    <div class="nku-meta-list">
                        <div class="nku-meta-item">
                            <label>Статус</label>
                            <div>
                                <span class="nku-badge nku-badge--status-<?= $model->status ?>">
                                    <?= $model->getStatusLabel() ?>
                                </span>
                            </div>
                        </div>

                        <div class="nku-meta-item">
                            <label>Приоритет</label>
                            <div>
                                <span class="nku-badge nku-badge--priority-<?= $model->priority ?>">
                                    <?= $model->getPriorityLabel() ?>
                                </span>
                            </div>
                        </div>

                        <div class="nku-meta-item">
                            <label>Прогресс</label>
                            <div class="fw-semibold" id="meta-progress">
                                <?php
                                $totalSubtasks = $model->getTotalSubtasksCount();
                                if ($totalSubtasks > 0):
                                    echo $model->getProgressFormat() . ' (' . $model->progress . '%)';
                                else:
                                    echo $model->progress . '%';
                                endif;
                                ?>
                            </div>
                        </div>

                        <?php if ($model->project->department): ?>
                            <div class="nku-meta-item">
                                <label>Подразделение</label>
                                <div><?= Html::encode($model->project->department->name) ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Task Description */
.task-description {
    line-height: 1.7;
    font-size: 1rem;
}

/* Comments */
.nku-comments-list {
    max-height: 500px;
    overflow-y: auto;
}

.nku-comment {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    border-bottom: 1px solid var(--nku-color-border);
}

.nku-comment:last-child {
    border-bottom: none;
}

.nku-comment__avatar {
    flex-shrink: 0;
    font-size: 2rem;
    color: var(--nku-color-primary);
}

.nku-comment__content {
    flex-grow: 1;
}

.nku-comment__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.nku-comment__author {
    font-weight: 600;
    color: var(--nku-color-text-primary);
}

.nku-comment__time {
    font-size: 0.75rem;
    color: var(--nku-color-text-secondary);
}

.nku-comment__text {
    color: var(--nku-color-text-secondary);
    line-height: 1.6;
}

/* Timeline */
.nku-timeline {
    position: relative;
    padding-left: 2rem;
}

.nku-timeline::before {
    content: '';
    position: absolute;
    left: 0.75rem;
    top: 0;
    bottom: 0;
    width: 2px;
    background: var(--nku-color-border);
}

.nku-timeline__item {
    position: relative;
    padding-bottom: 1.5rem;
}

.nku-timeline__item:last-child {
    padding-bottom: 0;
}

.nku-timeline__icon {
    position: absolute;
    left: -2rem;
    width: 2rem;
    height: 2rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.75rem;
    z-index: 1;
}

.nku-timeline__icon--success { background: var(--nku-color-success); }
.nku-timeline__icon--primary { background: var(--nku-color-primary); }
.nku-timeline__icon--warning { background: var(--nku-color-warning); }
.nku-timeline__icon--danger { background: var(--nku-color-danger); }
.nku-timeline__icon--info { background: var(--nku-color-info); }

.nku-timeline__title {
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.nku-timeline__time {
    font-size: 0.875rem;
    color: var(--nku-color-text-secondary);
}

/* Meta List */
.nku-meta-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.nku-meta-item label {
    font-size: 0.75rem;
    text-transform: uppercase;
    color: var(--nku-color-text-secondary);
    font-weight: 600;
    margin-bottom: 0.25rem;
    display: block;
}

.nku-meta-item div {
    font-size: 0.875rem;
}

/* Empty state small variant */
.nku-empty--sm {
    padding: 2rem 1rem;
}

.nku-empty--sm .nku-empty__icon {
    font-size: 2rem;
}

.nku-empty--sm .nku-empty__title {
    font-size: 0.875rem;
}

.nku-empty--sm .nku-empty__description {
    font-size: 0.75rem;
}

/* Subtasks */
.subtasks-list {
    max-height: 400px;
    overflow-y: auto;
}

.subtask-item {
    transition: background-color 0.2s;
}

.subtask-item:hover {
    background-color: var(--nku-color-bg-hover, #f8f9fa) !important;
}

.subtask-checkbox:disabled {
    cursor: not-allowed !important;
    opacity: 0.6;
}

/* Кнопка "К доске" с рамками */
.nku-btn--info.nku-btn--outline {
    border: 2px solid var(--nku-color-info, #7DB4B5) !important;
    background-color: transparent !important;
    color: var(--nku-color-info, #7DB4B5) !important;
}

.nku-btn--info.nku-btn--outline:hover {
    background-color: var(--nku-color-info, #7DB4B5) !important;
    color: white !important;
    border-color: var(--nku-color-info, #7DB4B5) !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Получаем CSRF токен из PHP
    var csrfToken = '<?= Yii::$app->request->csrfToken ?>';
    var csrfParam = '<?= Yii::$app->request->csrfParam ?>';
    
    // Обработка изменения статуса подзадачи
    document.querySelectorAll('.subtask-checkbox').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            if (this.disabled) {
                return;
            }
            
            var checkboxElement = this;
            var taskId = this.getAttribute('data-task-id');
            var subtaskIndex = parseInt(this.getAttribute('data-subtask-index'));
            var isCompleted = this.checked;
            
            // Блокируем чекбокс на время запроса
            checkboxElement.disabled = true;
            
            // Формируем тело запроса
            var formData = new URLSearchParams();
            formData.append('task_id', taskId);
            formData.append('subtask_index', subtaskIndex);
            formData.append('completed', isCompleted ? '1' : '0');
            formData.append(csrfParam, csrfToken);
            
            console.log('Отправка запроса:', {
                taskId: taskId,
                subtaskIndex: subtaskIndex,
                completed: isCompleted
            });
            
            // Отправляем AJAX запрос
            fetch('<?= Url::to(['task/toggle-subtask']) ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formData.toString()
            })
            .then(response => {
                console.log('Ответ получен, статус:', response.status);
                if (!response.ok) {
                    throw new Error('HTTP error! status: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                console.log('Данные ответа:', data);
                checkboxElement.disabled = false;
                
                if (data.success) {
                    // Обновляем визуальное состояние подзадачи
                    var subtaskItem = checkboxElement.closest('.subtask-item');
                    var subtaskText = subtaskItem.querySelector('.subtask-text');
                    
                    if (isCompleted) {
                        subtaskItem.classList.add('bg-light');
                        if (subtaskText) {
                            subtaskText.classList.add('text-decoration-line-through', 'text-muted');
                        }
                    } else {
                        subtaskItem.classList.remove('bg-light');
                        if (subtaskText) {
                            subtaskText.classList.remove('text-decoration-line-through', 'text-muted');
                        }
                    }
                    
                    // Обновляем прогресс, если он отображается
                    var progressFormat = data.progress_format || '0/0';
                    var progressPercent = data.progress || 0;
                    
                    // Обновляем счетчик прогресса в заголовке подзадач
                    var subtasksBadge = document.getElementById('subtasks-progress-badge');
                    if (subtasksBadge) {
                        subtasksBadge.textContent = progressFormat;
                    }
                    
                    // Обновляем прогресс-бар
                    var progressBar = document.getElementById('progress-bar');
                    var progressLabel = document.getElementById('progress-label');
                    if (progressBar) {
                        progressBar.style.width = progressPercent + '%';
                        progressBar.className = 'nku-progress__bar nku-progress__bar--' + 
                            (progressPercent >= 100 ? 'success' : (progressPercent >= 50 ? 'primary' : 'warning'));
                    }
                    if (progressLabel) {
                        progressLabel.textContent = progressPercent + '%';
                    }
                    
                    // Обновляем текст прогресса в заголовке
                    var progressFormatText = document.getElementById('progress-format-text');
                    var progressPercentText = document.getElementById('progress-percent-text');
                    if (progressFormatText) {
                        progressFormatText.textContent = progressFormat;
                    }
                    if (progressPercentText) {
                        progressPercentText.textContent = '(' + progressPercent + '%)';
                    }
                    
                    // Обновляем прогресс в мета-информации
                    var metaProgress = document.getElementById('meta-progress');
                    if (metaProgress) {
                        var totalSubtasks = progressFormat.split('/')[1];
                        if (totalSubtasks && parseInt(totalSubtasks) > 0) {
                            metaProgress.textContent = progressFormat + ' (' + progressPercent + '%)';
                        } else {
                            metaProgress.textContent = progressPercent + '%';
                        }
                    }
                } else {
                    // Откатываем изменение
                    checkboxElement.checked = !isCompleted;
                    alert(data.message || 'Ошибка при изменении статуса подзадачи');
                }
            })
            .catch(error => {
                // Откатываем изменение
                checkboxElement.checked = !isCompleted;
                checkboxElement.disabled = false;
                console.error('Ошибка:', error);
                alert('Ошибка при изменении статуса подзадачи: ' + error.message);
            });
        });
    });
});
</script>
