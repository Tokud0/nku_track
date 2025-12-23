<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\models\Project;
use app\models\Task;
use app\models\User;

/** @var yii\web\View $this */
/** @var app\models\Project $model */

$this->title = 'Канбан-доска: ' . $model->title;
$this->params['breadcrumbs'][] = ['label' => 'Проекты', 'url' => ['project/index']];
$this->params['breadcrumbs'][] = ['label' => $model->title, 'url' => ['project/view', 'id' => (string)$model->_id]];
$this->params['breadcrumbs'][] = 'Канбан-доска';

$user = Yii::$app->user->identity;
$tasksQuery = Task::find()->where(['project_id' => $model->_id]);

// Фильтруем задачи по видимости для исполнителя
if ($user->role === User::ROLE_EXECUTOR) {
    $allTasks = Task::find()->where(['project_id' => $model->_id])->all();
    $visibleTaskIds = [];
    foreach ($allTasks as $task) {
        if ($task->isAssignedToUser($user)) {
            $visibleTaskIds[] = $task->_id;
        }
    }
    if (!empty($visibleTaskIds)) {
        $tasksQuery->andWhere(['_id' => ['$in' => $visibleTaskIds]]);
    } else {
        $tasksQuery->andWhere(['_id' => ['$in' => []]]); // Пустой результат
    }
}

$tasks = $tasksQuery->all();
$tasksByStatus = [
    Task::STATUS_TODO => [],
    Task::STATUS_IN_PROGRESS => [],
    Task::STATUS_REVIEW => [],
    Task::STATUS_DONE => [],
    Task::STATUS_CANCELED => [],
];

foreach ($tasks as $task) {
    if (isset($tasksByStatus[$task->status])) {
        $tasksByStatus[$task->status][] = $task;
    }
}

// Определяем права пользователя
$canCreateTask = in_array($user->role, [User::ROLE_MANAGER, User::ROLE_TOP_MANAGER, User::ROLE_RECTOR, User::ROLE_ADMIN]);
$canDragTasks = true; // По умолчанию все могут перетаскивать
$canEditTasks = in_array($user->role, [User::ROLE_MANAGER, User::ROLE_TOP_MANAGER, User::ROLE_RECTOR, User::ROLE_ADMIN]);

// UI-флаг режима ректора
$RECTOR_TASKS_MODE = "readonly"; // "readonly" или "limited"

// Ректор: определяем режим работы с задачами
$isRectorReadonly = false;
$isRectorLimited = false;
if ($user->role === User::ROLE_RECTOR) {
    if ($RECTOR_TASKS_MODE === "readonly") {
        $canDragTasks = false; // Отключаем drag
        $isRectorReadonly = true;
    } elseif ($RECTOR_TASKS_MODE === "limited") {
        $canDragTasks = false; // Drag всё равно отключен
        $isRectorLimited = true; // Будем показывать кнопки смены статуса
    }
}

// Исполнитель тоже может перетаскивать свои задачи
if ($user->role === User::ROLE_EXECUTOR) {
    $canDragTasks = true;
}

// Колонки статусов
$statusColumns = [
    Task::STATUS_TODO => [
        'label' => 'К выполнению',
        'icon' => 'fa-clipboard-list',
        'color' => 'secondary',
        'bgClass' => 'bg-secondary',
    ],
    Task::STATUS_IN_PROGRESS => [
        'label' => 'В работе',
        'icon' => 'fa-spinner',
        'color' => 'primary',
        'bgClass' => 'bg-primary',
    ],
    Task::STATUS_REVIEW => [
        'label' => 'На проверке',
        'icon' => 'fa-eye',
        'color' => 'warning',
        'bgClass' => 'bg-warning',
    ],
    Task::STATUS_DONE => [
        'label' => 'Завершено',
        'icon' => 'fa-check-circle',
        'color' => 'success',
        'bgClass' => 'bg-success',
    ],
    Task::STATUS_CANCELED => [
        'label' => 'Отменено',
        'icon' => 'fa-times-circle',
        'color' => 'danger',
        'bgClass' => 'bg-danger',
    ],
];

// Маппинг ролей для баннера прав
$roleInfo = [
    User::ROLE_ADMIN => ['label' => 'Администратор', 'icon' => 'fa-shield-alt', 'desc' => 'Полный доступ ко всем задачам'],
    User::ROLE_RECTOR => ['label' => 'Руководитель', 'icon' => 'fa-crown', 'desc' => $isRectorReadonly ? 'Только просмотр задач' : 'Управление задачами с ограничениями'],
    User::ROLE_TOP_MANAGER => ['label' => 'Топ-менеджер', 'icon' => 'fa-star', 'desc' => 'Создание и управление задачами'],
    User::ROLE_MANAGER => ['label' => 'Менеджер', 'icon' => 'fa-user-tie', 'desc' => 'Создание и управление задачами'],
    User::ROLE_EXECUTOR => ['label' => 'Исполнитель', 'icon' => 'fa-user', 'desc' => 'Работа со своими задачами'],
];
$currentRoleInfo = $roleInfo[$user->role] ?? ['label' => $user->role, 'icon' => 'fa-user', 'desc' => ''];
?>

<div class="project-kanban">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1">
                <i class="fas fa-columns me-2"></i>
                <?= Html::encode($model->title) ?>
            </h1>
            <p class="text-muted mb-0">Канбан-доска задач проекта</p>
        </div>
        <div class="d-flex gap-2">
            <?php if ($canCreateTask): ?>
                <?= Html::a(
                    '<i class="fas fa-plus-circle me-2"></i>Создать задачу',
                    ['task/create', 'project_id' => (string)$model->_id],
                    ['class' => 'nku-btn nku-btn--success']
                ) ?>
            <?php endif; ?>
            <?= Html::a(
                '<i class="fas fa-arrow-left me-2"></i>К проекту',
                ['project/view', 'id' => (string)$model->_id],
                ['class' => 'nku-btn nku-btn--secondary']
            ) ?>
        </div>
    </div>

    <!-- Баннер "Ваши права" -->
    <div class="nku-card mb-4">
        <div class="nku-card__body py-3">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas <?= $currentRoleInfo['icon'] ?> text-primary" style="font-size: 1.5rem;"></i>
                        <div>
                            <div class="fw-semibold">Ваша роль: <?= $currentRoleInfo['label'] ?></div>
                            <small class="text-muted"><?= $currentRoleInfo['desc'] ?></small>
                        </div>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <?php if ($canCreateTask): ?>
                        <span class="nku-badge nku-badge--success">
                            <i class="fas fa-plus me-1"></i>Создание задач
                        </span>
                    <?php endif; ?>
                    <?php if ($canDragTasks): ?>
                        <span class="nku-badge nku-badge--primary">
                            <i class="fas fa-arrows-alt me-1"></i>Перемещение задач
                        </span>
                    <?php else: ?>
                        <span class="nku-badge nku-badge--secondary">
                            <i class="fas fa-lock me-1"></i>Перемещение отключено
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Баннер для readonly режима ректора -->
    <?php if ($isRectorReadonly): ?>
        <div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
            <i class="fas fa-info-circle me-3" style="font-size: 1.5rem;"></i>
            <div>
                <strong>Режим просмотра</strong>
                <div class="small">Вы можете просматривать задачи, но перемещение отключено. Для изменения статуса используйте страницу просмотра задачи.</div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Kanban Board -->
    <div class="kanban-board">
        <div class="row g-3">
            <?php foreach ($statusColumns as $status => $columnInfo): ?>
                <div class="col-lg-2dot4 col-md-4 col-sm-6">
                    <div class="nku-kanban-column" data-status="<?= $status ?>">
                        <!-- Column Header -->
                        <div class="nku-kanban-column__header <?= $columnInfo['bgClass'] ?>">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fas <?= $columnInfo['icon'] ?>"></i>
                                    <span class="fw-semibold"><?= $columnInfo['label'] ?></span>
                                </div>
                                <span class="badge bg-white text-dark" id="count-<?= $status ?>">
                                    <?= count($tasksByStatus[$status]) ?>
                                </span>
                            </div>
                        </div>

                        <!-- Column Body -->
                        <div class="nku-kanban-column__body <?= !$canDragTasks ? 'no-drag' : '' ?>" 
                             id="column-<?= $status ?>"
                             data-drag-enabled="<?= $canDragTasks ? 'true' : 'false' ?>">
                            
                            <?php if (empty($tasksByStatus[$status])): ?>
                                <!-- Empty State -->
                                <div class="nku-kanban-empty">
                                    <i class="fas <?= $columnInfo['icon'] ?> nku-kanban-empty__icon"></i>
                                    <div class="nku-kanban-empty__text">Нет задач</div>
                                </div>
                            <?php else: ?>
                                <?php foreach ($tasksByStatus[$status] as $task): ?>
                                    <!-- Task Card -->
                                    <div class="nku-task-card <?= !$canDragTasks ? 'no-drag' : '' ?>" 
                                         data-task-id="<?= (string)$task->_id ?>" 
                                         data-priority="<?= $task->priority ?>"
                                         <?= $canDragTasks ? 'draggable="true"' : '' ?>>
                                        
                                        <div class="nku-task-card__priority nku-task-card__priority--<?= $task->priority ?>"></div>
                                        
                                        <div class="nku-task-card__body">
                                            <!-- Title -->
                                            <h6 class="nku-task-card__title">
                                                <?= Html::a(
                                                    Html::encode($task->title),
                                                    ['task/view', 'id' => (string)$task->_id],
                                                    ['class' => 'text-decoration-none text-dark']
                                                ) ?>
                                            </h6>

                                            <!-- Priority Badge -->
                                            <div class="mb-2">
                                                <span class="nku-badge nku-badge--sm nku-badge--priority-<?= $task->priority ?>">
                                                    <?= $task->getPriorityLabel() ?>
                                                </span>
                                            </div>

                                            <!-- Assignee -->
                                            <?php if ($task->getExecutorDisplayName()): ?>
                                                <div class="nku-task-card__meta mb-2">
                                                    <i class="fas fa-user text-muted me-1"></i>
                                                    <small class="text-muted"><?= Html::encode($task->getExecutorDisplayName()) ?></small>
                                                </div>
                                            <?php endif; ?>

                                            <!-- Due Date -->
                                            <?php if ($task->due_date instanceof \MongoDB\BSON\UTCDateTime): ?>
                                                <?php
                                                $dueDate = $task->due_date->toDateTime()->getTimestamp();
                                                $now = time();
                                                $daysLeft = floor(($dueDate - $now) / (24 * 60 * 60));
                                                $isOverdue = $daysLeft < 0;
                                                $isUrgent = $daysLeft >= 0 && $daysLeft <= 3;
                                                ?>
                                                <div class="nku-task-card__meta mb-2">
                                                    <i class="fas fa-calendar text-muted me-1"></i>
                                                    <small class="<?= $isOverdue ? 'text-danger fw-bold' : ($isUrgent ? 'text-warning fw-bold' : 'text-muted') ?>">
                                                        <?= date('d.m.Y', $dueDate) ?>
                                                        <?php if ($isOverdue): ?>
                                                            <span class="nku-badge nku-badge--xs nku-badge--danger ms-1">Просрочено</span>
                                                        <?php elseif ($isUrgent): ?>
                                                            <span class="nku-badge nku-badge--xs nku-badge--warning ms-1">Скоро</span>
                                                        <?php endif; ?>
                                                    </small>
                                                </div>
                                            <?php endif; ?>

                                            <!-- Progress -->
                                            <?php if ($task->progress > 0): ?>
                                                <div class="nku-progress nku-progress--sm mb-2">
                                                    <div class="nku-progress__bar nku-progress__bar--primary" 
                                                         style="width: <?= $task->progress ?>%">
                                                    </div>
                                                </div>
                                                <div class="text-end">
                                                    <small class="text-muted"><?= $task->progress ?>%</small>
                                                </div>
                                            <?php endif; ?>

                                            <!-- Actions -->
                                            <div class="nku-task-card__actions">
                                                <?= Html::a(
                                                    '<i class="fas fa-eye me-1"></i>Просмотр',
                                                    ['task/view', 'id' => (string)$task->_id],
                                                    ['class' => 'nku-btn nku-btn--xs nku-btn--outline-primary']
                                                ) ?>
                                                <?php 
                                                $canEdit = $canEditTasks || ($user->role === User::ROLE_EXECUTOR && $task->isAssignedToUser($user));
                                                if ($canEdit): ?>
                                                    <?= Html::a(
                                                        '<i class="fas fa-edit"></i>',
                                                        ['task/update', 'id' => (string)$task->_id],
                                                        [
                                                            'class' => 'nku-btn nku-btn--xs nku-btn--outline-secondary',
                                                            'title' => 'Редактировать',
                                                            'data-bs-toggle' => 'tooltip'
                                                        ]
                                                    ) ?>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Limited режим: кнопки смены статуса для ректора -->
                                            <?php if ($isRectorLimited): ?>
                                                <div class="nku-task-card__status-controls mt-2 pt-2 border-top">
                                                    <small class="text-muted d-block mb-1">Изменить статус:</small>
                                                    <div class="d-flex gap-1 flex-wrap">
                                                        <?php foreach ([Task::STATUS_TODO, Task::STATUS_IN_PROGRESS, Task::STATUS_REVIEW, Task::STATUS_DONE] as $targetStatus): ?>
                                                            <?php if ($targetStatus !== $task->status): ?>
                                                                <button class="nku-btn nku-btn--xs nku-btn--outline-<?= $statusColumns[$targetStatus]['color'] ?> change-status-btn"
                                                                        data-task-id="<?= (string)$task->_id ?>"
                                                                        data-new-status="<?= $targetStatus ?>"
                                                                        title="<?= $statusColumns[$targetStatus]['label'] ?>">
                                                                    <i class="fas <?= $statusColumns[$targetStatus]['icon'] ?>"></i>
                                                                </button>
                                                            <?php endif; ?>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<style>
/* Kanban Column */
.nku-kanban-column {
    background: var(--nku-color-background);
    border-radius: var(--nku-border-radius);
    border: 2px solid var(--nku-color-border);
    overflow: hidden;
    height: 100%;
    display: flex;
    flex-direction: column;
}

.nku-kanban-column__header {
    color: white;
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
}

.nku-kanban-column__body {
    padding: 1rem;
    min-height: 500px;
    max-height: 70vh;
    overflow-y: auto;
    flex: 1;
}

.nku-kanban-column__body.no-drag {
    cursor: default;
}

.nku-kanban-column__body.drag-over {
    background-color: var(--nku-color-primary-light);
    border: 2px dashed var(--nku-color-primary);
}

/* Task Card */
.nku-task-card {
    background: white;
    border-radius: var(--nku-border-radius);
    border: 1px solid var(--nku-color-border);
    margin-bottom: 0.75rem;
    position: relative;
    overflow: hidden;
    transition: all 0.2s ease;
}

.nku-task-card:not(.no-drag) {
    cursor: move;
}

.nku-task-card.no-drag {
    cursor: default;
}

.nku-task-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}

.nku-task-card.dragging {
    opacity: 0.5;
    transform: rotate(3deg);
}

.nku-task-card__priority {
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
}

.nku-task-card__priority--critical { background: var(--nku-color-danger); }
.nku-task-card__priority--high { background: var(--nku-color-warning); }
.nku-task-card__priority--medium { background: var(--nku-color-info); }
.nku-task-card__priority--low { background: var(--nku-color-secondary); }

.nku-task-card__body {
    padding: 0.75rem;
    padding-left: 1rem;
}

.nku-task-card__title {
    font-size: 0.875rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    line-height: 1.4;
}

.nku-task-card__meta {
    font-size: 0.75rem;
    display: flex;
    align-items: center;
}

.nku-task-card__actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 0.75rem;
}

/* Empty State */
.nku-kanban-empty {
    text-align: center;
    padding: 3rem 1rem;
    color: var(--nku-color-text-secondary);
}

.nku-kanban-empty__icon {
    font-size: 2.5rem;
    opacity: 0.3;
    margin-bottom: 0.5rem;
    display: block;
}

.nku-kanban-empty__text {
    font-size: 0.875rem;
}

/* 5-column layout */
.col-lg-2dot4 {
    flex: 0 0 auto;
    width: 20%;
}

@media (max-width: 1199.98px) {
    .col-lg-2dot4 {
        width: 33.333333%;
    }
}

@media (max-width: 767.98px) {
    .col-lg-2dot4 {
        width: 100%;
    }
}

/* Readonly mode styling */
.nku-kanban-column__body.no-drag .nku-task-card {
    cursor: default;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const canDragTasks = <?= $canDragTasks ? 'true' : 'false' ?>;
    const isRectorLimited = <?= $isRectorLimited ? 'true' : 'false' ?>;
    
    // Инициализируем Sortable только если разрешено
    if (canDragTasks) {
        const columns = document.querySelectorAll('.nku-kanban-column__body');
        
        columns.forEach(function(column) {
            new Sortable(column, {
                group: 'kanban',
                animation: 150,
                ghostClass: 'dragging',
                dragClass: 'dragging',
                filter: '.no-drag',
                onEnd: function(evt) {
                    const taskId = evt.item.dataset.taskId;
                    const newStatus = evt.to.closest('.nku-kanban-column').dataset.status;
                    const oldStatus = evt.from.closest('.nku-kanban-column').dataset.status;
                    
                    if (newStatus === oldStatus) {
                        return;
                    }
                    
                    updateCounters();
                    changeTaskStatus(taskId, newStatus);
                }
            });
        });
        
        // Визуальная обратная связь при перетаскивании
        columns.forEach(function(column) {
            column.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.classList.add('drag-over');
            });
            
            column.addEventListener('dragleave', function(e) {
                this.classList.remove('drag-over');
            });
            
            column.addEventListener('drop', function(e) {
                this.classList.remove('drag-over');
            });
        });
    }
    
    // Limited режим: кнопки смены статуса
    if (isRectorLimited) {
        document.querySelectorAll('.change-status-btn').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const taskId = this.dataset.taskId;
                const newStatus = this.dataset.newStatus;
                
                if (confirm('Изменить статус задачи?')) {
                    changeTaskStatus(taskId, newStatus, true);
                }
            });
        });
    }
    
    // Функция для обновления счетчиков
    function updateCounters() {
        const statuses = ['<?= Task::STATUS_TODO ?>', '<?= Task::STATUS_IN_PROGRESS ?>', '<?= Task::STATUS_REVIEW ?>', '<?= Task::STATUS_DONE ?>', '<?= Task::STATUS_CANCELED ?>'];
        statuses.forEach(function(status) {
            const column = document.getElementById('column-' + status);
            if (!column) return;
            
            const tasks = column.querySelectorAll('.nku-task-card');
            const count = tasks.length;
            const countElement = document.getElementById('count-' + status);
            if (countElement) {
                countElement.textContent = count;
            }
            
            // Показываем/скрываем empty state
            const emptyState = column.querySelector('.nku-kanban-empty');
            if (count === 0 && !emptyState) {
                const statusInfo = getStatusInfo(status);
                column.innerHTML = `
                    <div class="nku-kanban-empty">
                        <i class="fas ${statusInfo.icon} nku-kanban-empty__icon"></i>
                        <div class="nku-kanban-empty__text">Нет задач</div>
                    </div>
                `;
            } else if (count > 0 && emptyState) {
                emptyState.remove();
            }
        });
    }
    
    function getStatusInfo(status) {
        const statusMap = {
            '<?= Task::STATUS_TODO ?>': { icon: 'fa-clipboard-list' },
            '<?= Task::STATUS_IN_PROGRESS ?>': { icon: 'fa-spinner' },
            '<?= Task::STATUS_REVIEW ?>': { icon: 'fa-eye' },
            '<?= Task::STATUS_DONE ?>': { icon: 'fa-check-circle' },
            '<?= Task::STATUS_CANCELED ?>': { icon: 'fa-times-circle' },
        };
        return statusMap[status] || { icon: 'fa-question' };
    }
    
    // Функция для изменения статуса задачи через AJAX
    function changeTaskStatus(taskId, newStatus, reload = false) {
        const url = '<?= Url::to(['task/change-status']) ?>';
        const csrfToken = '<?= Yii::$app->request->csrfToken ?>';
        
        const formData = new FormData();
        formData.append('<?= Yii::$app->request->csrfParam ?>', csrfToken);
        
        fetch(url + '?id=' + taskId + '&status=' + newStatus, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: formData,
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Статус задачи успешно изменен', 'success');
                if (reload) {
                    setTimeout(() => location.reload(), 1000);
                }
            } else {
                showNotification(data.message || 'Ошибка при изменении статуса', 'error');
                if (!reload) {
                    location.reload();
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Ошибка при изменении статуса', 'error');
            location.reload();
        });
    }
    
    // Функция для показа уведомлений
    function showNotification(message, type) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const notification = document.createElement('div');
        notification.className = 'alert ' + alertClass + ' alert-dismissible fade show position-fixed';
        notification.style.cssText = 'top: 80px; right: 20px; z-index: 9999; min-width: 300px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);';
        notification.innerHTML = `
            <strong>${type === 'success' ? '✓' : '✗'}</strong> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        document.body.appendChild(notification);
        
        setTimeout(function() {
            notification.remove();
        }, 3000);
    }
    
    // Инициализация tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
