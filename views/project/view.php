<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\models\Project;
use app\models\ProjectSpec;
use app\models\Task;
use app\models\User;
use app\models\GlobalProjectRole;

/** @var yii\web\View $this */
/** @var app\models\Project $model */
/** @var app\models\ProjectSpec $spec */
/** @var app\models\Task[] $tasks */
/** @var string|null $userGlobalRole */

$this->title = $model->title;
$this->params['breadcrumbs'][] = ['label' => 'Проекты', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$user = Yii::$app->user->identity;
$userGlobalRole = $userGlobalRole ?? \app\models\GlobalProjectRole::getUserRole($user->_id);

// Проверка прав на редактирование
$canEdit = false;
if ($model->isGlobal()) {
    // Глобальный проект: админ, глоб. руководитель, глоб. топ-менеджер
    $canEdit = $user->role === User::ROLE_ADMIN || 
        $userGlobalRole === GlobalProjectRole::ROLE_RECTOR || 
        $userGlobalRole === GlobalProjectRole::ROLE_GLOBAL_TOP_MANAGER;
} elseif ($user->role === User::ROLE_ADMIN) {
    $canEdit = true;
} elseif ($user->role === User::ROLE_HEAD) {
    if ($model->department_id && $user->department_id &&
        (string)$model->department_id === (string)$user->department_id) {
        $canEdit = true;
    }
} elseif ($user->role === User::ROLE_RECTOR) {
    $canEdit = false;
} elseif ($user->role === User::ROLE_TOP_MANAGER) {
    if ($model->department_id && $user->department_id &&
        (string)$model->department_id === (string)$user->department_id) {
        $canEdit = true;
    }
}

// Проверка прав на работу с ТЗ
$canWorkWithSpec = false;
if ($model->isGlobal()) {
    $canWorkWithSpec = $canEdit;
} elseif ($user->role === User::ROLE_ADMIN) {
    $canWorkWithSpec = true;
} elseif ($user->role === User::ROLE_TOP_MANAGER) {
    if ($model->department_id && $user->department_id &&
        (string)$model->department_id === (string)$user->department_id) {
        $canWorkWithSpec = true;
    }
} elseif ($user->role === User::ROLE_HEAD) {
    if ($model->department_id && $user->department_id &&
        (string)$model->department_id === (string)$user->department_id) {
        $canWorkWithSpec = true;
    }
}
if ($user->role === User::ROLE_RECTOR && !$model->isGlobal()) {
    $canWorkWithSpec = false;
}

// Проверка прав на создание задач
if ($model->isGlobal()) {
    // Глобальный проект: админ, глоб. руководитель, глоб. топ-менеджер, глоб. менеджер (НЕ глоб. исполнитель)
    $canCreateTask = $user->role === User::ROLE_ADMIN || 
        in_array($userGlobalRole, [
            GlobalProjectRole::ROLE_RECTOR,
            GlobalProjectRole::ROLE_GLOBAL_TOP_MANAGER,
            GlobalProjectRole::ROLE_GLOBAL_MANAGER,
        ]);
} else {
    $canCreateTask = in_array($user->role, [User::ROLE_MANAGER, User::ROLE_TOP_MANAGER, User::ROLE_HEAD, User::ROLE_ADMIN]);
}

// Задачи переданы из контроллера (уже отфильтрованы для прикреплённых из другого подразделения)
if (!isset($tasks)) {
    $tasks = [];
}
$tasksCount = count($tasks);
$tasksByStatus = [
    Task::STATUS_TODO => 0,
    Task::STATUS_IN_PROGRESS => 0,
    Task::STATUS_REVIEW => 0,
    Task::STATUS_DONE => 0,
];
foreach ($tasks as $task) {
    if (isset($tasksByStatus[$task->status])) {
        $tasksByStatus[$task->status]++;
    }
}
?>

<div class="project-view">
    <!-- Project Header -->
    <div class="nku-card mb-4">
        <div class="nku-card__body">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <h1 class="mb-0"><?= Html::encode($this->title) ?></h1>
                        <span class="nku-badge nku-badge--lg nku-badge--status-<?= $model->status ?>">
                            <?= $model->getStatusLabel() ?>
                        </span>
                    </div>
                    <p class="text-muted mb-0">
                        <i class="fas fa-building me-2"></i>
                        <?= $model->department ? Html::encode($model->department->name) : 'Без подразделения' ?>
                        <span class="mx-2">•</span>
                        <i class="fas fa-user-tie me-2"></i>
                        <?= $model->manager ? Html::encode($model->manager->fio) : 'Без руководителя' ?>
                    </p>
                </div>
                <div class="text-end">
                    <?= Html::a(
                        '<i class="fas fa-arrow-left me-2"></i>Назад',
                        $model->isGlobal() ? ($model->direction_id ? ['/direction/view', 'id' => (string)$model->direction_id] : ['/direction/index']) : ['index'],
                        ['class' => 'nku-btn nku-btn--secondary mb-2']
                    ) ?>
                    <?= Html::a(
                        '<i class="fas fa-project-diagram me-2"></i>Mind Map',
                        ['mind-map', 'id' => (string)$model->_id],
                        ['class' => 'nku-btn nku-btn--info mb-2']
                    ) ?>
                    <?php if ($canEdit): ?>
                        <div class="d-flex gap-2">
                            <?= Html::a(
                                '<i class="fas fa-edit me-2"></i>Редактировать',
                                ['update', 'id' => (string)$model->_id],
                                ['class' => 'nku-btn nku-btn--primary']
                            ) ?>
                            <?= Html::a(
                                '<i class="fas fa-trash me-2"></i>Удалить',
                                ['delete', 'id' => (string)$model->_id],
                                [
                                    'class' => 'nku-btn nku-btn--danger',
                                    'data' => [
                                        'confirm' => 'Вы уверены, что хотите удалить этот проект?',
                                        'method' => 'post',
                                    ],
                                ]
                            ) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Progress & Deadline -->
            <!-- Временно скрыто по запросу пользователя -->
            <?php if (false): ?>
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label fw-semibold mb-2">
                        <i class="fas fa-chart-line me-2"></i>
                        Прогресс проекта
                    </label>
                    <div class="nku-progress nku-progress--lg">
                        <div class="nku-progress__bar nku-progress__bar--<?= $model->progress >= 100 ? 'success' : ($model->progress >= 50 ? 'primary' : 'warning') ?>" 
                             style="width: <?= $model->progress ?>%">
                            <span class="nku-progress__label"><?= $model->progress ?>%</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold mb-2">
                        <i class="fas fa-clock me-2"></i>
                        Следующий отчёт
                    </label>
                    <div>
                        <?php if ($model->next_report_deadline instanceof \MongoDB\BSON\UTCDateTime): ?>
                            <?php 
                            $deadline = $model->next_report_deadline->toDateTime()->getTimestamp();
                            $now = time();
                            $daysLeft = floor(($deadline - $now) / (24 * 60 * 60));
                            $isOverdue = $daysLeft < 0;
                            $isUrgent = $daysLeft <= 3 && !$isOverdue;
                            ?>
                            <div class="p-2 rounded <?= $isOverdue ? 'bg-danger bg-opacity-10' : ($isUrgent ? 'bg-warning bg-opacity-10' : 'bg-light') ?>">
                                <div class="fw-bold <?= $isOverdue ? 'text-danger' : ($isUrgent ? 'text-warning' : 'text-success') ?>">
                                    <?= date('d.m.Y H:i', $deadline) ?>
                                </div>
                                <small class="text-muted">
                                    <?php if ($isOverdue): ?>
                                        Просрочен на <?= abs($daysLeft) ?> дн.
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
            <?php endif; ?>
        </div>
    </div>

    <!-- Tabs Navigation -->
    <ul class="nav nav-tabs nku-tabs mb-4" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" 
                    type="button" role="tab" aria-controls="overview" aria-selected="true">
                <i class="fas fa-info-circle me-2"></i>
                Обзор
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="spec-tab" data-bs-toggle="tab" data-bs-target="#spec" 
                    type="button" role="tab" aria-controls="spec" aria-selected="false">
                <i class="fas fa-file-contract me-2"></i>
                Техническое задание
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tasks-tab" data-bs-toggle="tab" data-bs-target="#tasks" 
                    type="button" role="tab" aria-controls="tasks" aria-selected="false">
                <i class="fas fa-tasks me-2"></i>
                Задачи
                <span class="badge bg-primary ms-2"><?= $tasksCount ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <?= Html::a(
                '<i class="fas fa-project-diagram me-2"></i>Mind Map',
                ['mind-map', 'id' => (string)$model->_id],
                ['class' => 'nav-link']
            ) ?>
        </li>
    </ul>

    <!-- Tabs Content -->
    <div class="tab-content">
        <!-- Overview Tab -->
        <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview-tab">
            <div class="row">
                <div class="col-md-8">
                    <div class="nku-card mb-4">
                        <div class="nku-card__header">
                            <h5 class="mb-0">Описание проекта</h5>
                        </div>
                        <div class="nku-card__body">
                            <?php if ($model->description): ?>
                                <div class="project-description">
                                    <?= nl2br(Html::encode($model->description)) ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted mb-0">Описание не указано</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($model->goals): ?>
                        <div class="nku-card">
                            <div class="nku-card__header">
                                <h5 class="mb-0">Цели проекта</h5>
                            </div>
                            <div class="nku-card__body">
                                <div class="project-goals">
                                    <?= nl2br(Html::encode($model->goals)) ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="col-md-4">
                    <!-- Даты -->
                    <div class="nku-card mb-4">
                        <div class="nku-card__header">
                            <h5 class="mb-0">Сроки</h5>
                        </div>
                        <div class="nku-card__body">
                            <div class="mb-3">
                                <label class="text-muted mb-1">Начало проекта</label>
                                <div class="fw-semibold">
                                    <?php if ($model->start_date instanceof \MongoDB\BSON\UTCDateTime): ?>
                                        <i class="far fa-calendar-alt me-2"></i>
                                        <?= date('d.m.Y', $model->start_date->toDateTime()->getTimestamp()) ?>
                                    <?php else: ?>
                                        <span class="text-muted">Не указано</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div>
                                <label class="text-muted mb-1">Окончание проекта</label>
                                <div class="fw-semibold">
                                    <?php if ($model->end_date instanceof \MongoDB\BSON\UTCDateTime): ?>
                                        <i class="far fa-calendar-check me-2"></i>
                                        <?= date('d.m.Y', $model->end_date->toDateTime()->getTimestamp()) ?>
                                    <?php else: ?>
                                        <span class="text-muted">Не указано</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Метаданные -->
                    <div class="nku-card">
                        <div class="nku-card__header">
                            <h5 class="mb-0">Информация</h5>
                        </div>
                        <div class="nku-card__body">
                            <div class="mb-2">
                                <small class="text-muted">Создан</small>
                                <div>
                                    <?php if ($model->created_at instanceof \MongoDB\BSON\UTCDateTime): ?>
                                        <?= date('d.m.Y H:i', $model->created_at->toDateTime()->getTimestamp()) ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if ($model->updated_at instanceof \MongoDB\BSON\UTCDateTime): ?>
                                <div>
                                    <small class="text-muted">Обновлён</small>
                                    <div>
                                        <?= date('d.m.Y H:i', $model->updated_at->toDateTime()->getTimestamp()) ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Spec Tab -->
        <div class="tab-pane fade" id="spec" role="tabpanel" aria-labelledby="spec-tab">
            <div class="nku-card">
                <div class="nku-card__header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Техническое задание</h5>
                    <?php if ($canWorkWithSpec): ?>
                        <div>
                            <?php if ($spec): ?>
                                <?php if ($model->status === Project::STATUS_DRAFT): ?>
                                    <?= Html::a(
                                        '<i class="fas fa-edit me-2"></i>Редактировать ТЗ',
                                        ['project-spec/update', 'project_id' => (string)$model->_id],
                                        ['class' => 'nku-btn nku-btn--sm nku-btn--primary']
                                    ) ?>
                                <?php endif; ?>
                            <?php else: ?>
                                <?= Html::a(
                                    '<i class="fas fa-plus me-2"></i>Создать ТЗ',
                                    ['project-spec/create', 'project_id' => (string)$model->_id],
                                    ['class' => 'nku-btn nku-btn--sm nku-btn--success']
                                ) ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="nku-card__body">
                    <?php if ($spec): ?>
                        <!-- Описание ТЗ -->
                        <div class="mb-4">
                            <h6 class="text-muted mb-2">Описание</h6>
                            <div class="p-3 bg-light rounded">
                                <?= nl2br(Html::encode($spec->tz_text)) ?>
                            </div>
                        </div>

                        <!-- Период отчётности -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6 class="text-muted mb-2">Период отчётности</h6>
                                <span class="nku-badge nku-badge--primary">
                                    <?= $spec->getReportPeriodLabel() ?>
                                </span>
                                <?php if ($spec->report_period === ProjectSpec::PERIOD_CUSTOM && $spec->custom_period_days): ?>
                                    <div class="mt-1">
                                        <small class="text-muted">Каждые <?= $spec->custom_period_days ?> дней</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted mb-2">Дата создания ТЗ</h6>
                                <div>
                                    <?php if ($spec->created_at instanceof \MongoDB\BSON\UTCDateTime): ?>
                                        <i class="far fa-clock me-2"></i>
                                        <?= date('d.m.Y H:i', $spec->created_at->toDateTime()->getTimestamp()) ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Этапы проекта -->
                        <?php if (!empty($spec->milestones)): ?>
                            <div class="mb-4">
                                <h6 class="text-muted mb-3">Этапы проекта</h6>
                                <div class="list-group">
                                    <?php foreach ($spec->milestones as $index => $milestone): ?>
                                        <div class="list-group-item d-flex justify-content-between align-items-center milestone-item" data-milestone-index="<?= $index ?>">
                                            <div class="d-flex align-items-center">
                                                <?php if ($canWorkWithSpec): ?>
                                                    <!-- Чекбокс для пользователей с правами -->
                                                    <div class="form-check me-3">
                                                        <input class="form-check-input milestone-checkbox" 
                                                               type="checkbox" 
                                                               data-project-id="<?= (string)$model->_id ?>"
                                                               data-milestone-index="<?= $index ?>"
                                                               id="milestone-<?= $index ?>"
                                                               <?= (isset($milestone['done']) && $milestone['done']) ? 'checked' : '' ?>
                                                               style="width: 1.25rem; height: 1.25rem; cursor: pointer;">
                                                    </div>
                                                <?php else: ?>
                                                    <!-- Иконка для пользователей без прав -->
                                                    <?php if (isset($milestone['done']) && $milestone['done']): ?>
                                                        <i class="fas fa-check-circle text-success me-3" style="font-size: 1.25rem;"></i>
                                                    <?php else: ?>
                                                        <i class="far fa-circle text-secondary me-3" style="font-size: 1.25rem;"></i>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                                <span class="<?= (isset($milestone['done']) && $milestone['done']) ? 'text-decoration-line-through text-muted' : '' ?>">
                                                    <?= Html::encode($milestone['name'] ?? 'Без названия') ?>
                                                </span>
                                            </div>
                                            <?php if (isset($milestone['deadline']) && $milestone['deadline']): ?>
                                                <span class="nku-badge nku-badge--secondary">
                                                    <i class="far fa-calendar me-1"></i>
                                                    <?= Html::encode($milestone['deadline']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Метрики успеха -->
                        <?php if (!empty($spec->metrics)): ?>
                            <div class="mb-4">
                                <h6 class="text-muted mb-3">Метрики успеха</h6>
                                <div class="row">
                                    <?php foreach ($spec->metrics as $metric): ?>
                                        <div class="col-md-6 mb-2">
                                            <div class="d-flex align-items-start">
                                                <i class="fas fa-chart-line text-primary me-2 mt-1"></i>
                                                <span><?= Html::encode($metric) ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Шаблон отчёта -->
                        <?php if (!empty($spec->report_template)): ?>
                            <div>
                                <h6 class="text-muted mb-3">Шаблон отчёта</h6>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th width="30%">Поле</th>
                                                <th>Описание</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($spec->report_template as $key => $value): ?>
                                                <tr>
                                                    <td class="fw-semibold"><?= Html::encode($key) ?></td>
                                                    <td><?= Html::encode($value) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="nku-empty">
                            <div class="nku-empty__icon">
                                <i class="fas fa-file-contract"></i>
                            </div>
                            <div class="nku-empty__title">Техническое задание не создано</div>
                            <div class="nku-empty__description">
                                ТЗ определяет этапы, метрики и шаблоны отчётов для проекта
                            </div>
                            <?php if ($canWorkWithSpec): ?>
                                <div class="nku-empty__action">
                                    <?= Html::a(
                                        '<i class="fas fa-plus me-2"></i>Создать ТЗ',
                                        ['project-spec/create', 'project_id' => (string)$model->_id],
                                        ['class' => 'nku-btn nku-btn--primary']
                                    ) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Tasks Tab -->
        <div class="tab-pane fade" id="tasks" role="tabpanel" aria-labelledby="tasks-tab">
            <div class="nku-card">
                <div class="nku-card__header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Задачи проекта</h5>
                    <div class="d-flex gap-2">
                        <?php if ($canCreateTask): ?>
                            <?= Html::a(
                                '<i class="fas fa-plus me-2"></i>Создать задачу',
                                ['task/create', 'project_id' => (string)$model->_id],
                                ['class' => 'nku-btn nku-btn--sm nku-btn--success']
                            ) ?>
                        <?php endif; ?>
                        <?= Html::a(
                            '<i class="fas fa-list me-2"></i>Список задач',
                            ['tasks-list', 'id' => (string)$model->_id],
                            ['class' => 'nku-btn nku-btn--sm nku-btn--info']
                        ) ?>
                        <?= Html::a(
                            '<i class="fas fa-columns me-2"></i>Канбан-доска',
                            ['kanban', 'id' => (string)$model->_id],
                            ['class' => 'nku-btn nku-btn--sm nku-btn--primary']
                        ) ?>
                        <?php 
                        // Подсчитываем количество архивных задач
                        $archivedTasksCount = Task::find()
                            ->where(['project_id' => $model->_id, 'is_archived' => true])
                            ->count();
                        ?>
                        <?= Html::a(
                            '<i class="fas fa-archive me-2"></i>Архив' . ($archivedTasksCount > 0 ? ' <span class="badge bg-light text-dark ms-1">' . $archivedTasksCount . '</span>' : ''),
                            ['archive', 'id' => (string)$model->_id],
                            ['class' => 'nku-btn nku-btn--sm nku-btn--secondary']
                        ) ?>
                    </div>
                </div>
                <div class="nku-card__body">
                    <?php if ($tasksCount > 0): ?>
                        <!-- Статистика задач -->
                        <div class="row mb-4">
                            <div class="col-md-3 mb-3">
                                <div class="text-center p-3 bg-light rounded">
                                    <div class="h3 mb-1"><?= $tasksByStatus[Task::STATUS_TODO] ?></div>
                                    <small class="text-muted">К выполнению</small>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="text-center p-3 bg-primary bg-opacity-10 rounded">
                                    <div class="h3 mb-1 text-primary"><?= $tasksByStatus[Task::STATUS_IN_PROGRESS] ?></div>
                                    <small class="text-muted">В работе</small>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="text-center p-3 bg-warning bg-opacity-10 rounded">
                                    <div class="h3 mb-1 text-warning"><?= $tasksByStatus[Task::STATUS_REVIEW] ?></div>
                                    <small class="text-muted">На проверке</small>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="text-center p-3 bg-success bg-opacity-10 rounded">
                                    <div class="h3 mb-1 text-success"><?= $tasksByStatus[Task::STATUS_DONE] ?></div>
                                    <small class="text-muted">Завершено</small>
                                </div>
                            </div>
                        </div>

                        <div class="text-center">
                            <div class="d-flex gap-3 justify-content-center flex-wrap">
                                <?= Html::a(
                                    '<i class="fas fa-list me-2"></i>Список задач',
                                    ['tasks-list', 'id' => (string)$model->_id],
                                    ['class' => 'nku-btn nku-btn--lg nku-btn--info']
                                ) ?>
                                <?= Html::a(
                                    '<i class="fas fa-columns me-2"></i>Канбан-доска',
                                    ['kanban', 'id' => (string)$model->_id],
                                    ['class' => 'nku-btn nku-btn--lg nku-btn--primary']
                                ) ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="nku-empty">
                            <div class="nku-empty__icon">
                                <i class="fas fa-tasks"></i>
                            </div>
                            <div class="nku-empty__title">Задачи не созданы</div>
                            <div class="nku-empty__description">
                                Создайте первую задачу для проекта
                            </div>
                            <?php if ($canCreateTask): ?>
                                <div class="nku-empty__action">
                                    <?= Html::a(
                                        '<i class="fas fa-plus me-2"></i>Создать задачу',
                                        ['task/create', 'project_id' => (string)$model->_id],
                                        ['class' => 'nku-btn nku-btn--primary']
                                    ) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.nku-tabs {
    border-bottom: 2px solid var(--nku-color-border);
}

.nku-tabs .nav-link {
    border: none;
    color: var(--nku-color-text-secondary);
    padding: 1rem 1.5rem;
    border-bottom: 3px solid transparent;
    transition: all 0.2s ease;
}

.nku-tabs .nav-link:hover {
    color: var(--nku-color-primary);
    border-bottom-color: var(--nku-color-primary-light);
}

.nku-tabs .nav-link.active {
    color: var(--nku-color-primary);
    border-bottom-color: var(--nku-color-primary);
    background: transparent;
}

.project-description,
.project-goals {
    line-height: 1.7;
}
</style>

<?php if ($canWorkWithSpec && !empty($spec->milestones)): ?>
<?php
$csrfParam = Yii::$app->request->csrfParam;
$csrfToken = Yii::$app->request->csrfToken;
$baseUrl = Url::to(['project-spec/toggle-milestone', 'project_id' => (string)$model->_id]);
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.milestone-checkbox');
    const baseUrl = '<?= $baseUrl ?>';
    
    checkboxes.forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            const projectId = this.getAttribute('data-project-id');
            const milestoneIndex = this.getAttribute('data-milestone-index');
            const isChecked = this.checked;
            const milestoneItem = this.closest('.milestone-item');
            const milestoneName = milestoneItem.querySelector('span:not(.nku-badge)');
            
            // Блокируем чекбокс во время запроса
            this.disabled = true;
            
            // Формируем URL с параметрами
            const url = baseUrl;
            const formData = new URLSearchParams();
            formData.append('milestone_index', milestoneIndex);
            formData.append('<?= $csrfParam ?>', '<?= $csrfToken ?>');
            
            // Отправляем AJAX-запрос
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': '<?= $csrfToken ?>'
                },
                body: formData.toString()
            })
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    return response.text().then(text => {
                        console.error('Response error:', text);
                        throw new Error('HTTP error! status: ' + response.status);
                    });
                }
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.success) {
                    // Обновляем визуальное состояние
                    if (data.done) {
                        milestoneName.classList.add('text-decoration-line-through', 'text-muted');
                    } else {
                        milestoneName.classList.remove('text-decoration-line-through', 'text-muted');
                    }
                } else {
                    // Возвращаем чекбокс в исходное состояние при ошибке
                    this.checked = !isChecked;
                    alert(data.message || 'Произошла ошибка при обновлении статуса этапа.');
                }
                this.disabled = false;
            })
            .catch(error => {
                console.error('Fetch error:', error);
                // Возвращаем чекбокс в исходное состояние при ошибке
                this.checked = !isChecked;
                alert('Произошла ошибка при обновлении статуса этапа: ' + error.message);
                this.disabled = false;
            });
        });
    });
    
    // Проверяем наличие якоря #tasks в URL
    if (window.location.hash === '#tasks') {
        // Находим кнопку вкладки "Задачи" и активируем её
        var tasksTab = document.getElementById('tasks-tab');
        var tasksPane = document.getElementById('tasks');
        
        if (tasksTab && tasksPane) {
            // Убираем активное состояние с других вкладок
            var allTabs = document.querySelectorAll('.nav-link');
            var allPanes = document.querySelectorAll('.tab-pane');
            
            allTabs.forEach(function(tab) {
                tab.classList.remove('active');
                tab.setAttribute('aria-selected', 'false');
            });
            
            allPanes.forEach(function(pane) {
                pane.classList.remove('show', 'active');
            });
            
            // Активируем вкладку "Задачи"
            tasksTab.classList.add('active');
            tasksTab.setAttribute('aria-selected', 'true');
            tasksPane.classList.add('show', 'active');
            
            // Прокручиваем к вкладке
            tasksTab.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }
});
</script>
<?php endif; ?>
