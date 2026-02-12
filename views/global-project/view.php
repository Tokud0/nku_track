<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\models\Project;
use app\models\ProjectSpec;
use app\models\Task;
use app\models\User;
use app\models\GlobalProjectRole;
use app\models\ProjectDocument;

/** @var yii\web\View $this */
/** @var app\models\Project $model */
/** @var app\models\ProjectSpec $spec */
/** @var string|null $userGlobalRole */
/** @var ProjectDocument[] $documents */
/** @var bool $canManageDocuments */

$this->title = $model->title;
$this->params['breadcrumbs'][] = ['label' => 'Глобальный проект', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$user = Yii::$app->user->identity;
$documents = $documents ?? [];
$canManageDocuments = $canManageDocuments ?? false;

// Проверка прав на редактирование: админ, глоб. руководитель, глоб. топ-менеджер
$canEdit = $user->role === User::ROLE_ADMIN || 
    in_array($userGlobalRole, [GlobalProjectRole::ROLE_RECTOR, GlobalProjectRole::ROLE_GLOBAL_TOP_MANAGER]);

// Проверка прав на создание задач: админ, глоб. руководитель, глоб. топ-менеджер, глоб. менеджер (НЕ глоб. исполнитель)
$canCreateTask = $user->role === User::ROLE_ADMIN || 
    in_array($userGlobalRole, [
        GlobalProjectRole::ROLE_RECTOR,
        GlobalProjectRole::ROLE_GLOBAL_TOP_MANAGER,
        GlobalProjectRole::ROLE_GLOBAL_MANAGER,
    ]);


// Получение задач (исключаем архивные)
$tasksQuery = Task::find()
    ->where(['project_id' => $model->_id])
    ->andWhere(['$or' => [
        ['is_archived' => false],
        ['is_archived' => ['$exists' => false]],
    ]]);

// Фильтруем задачи по видимости: глоб. руководитель, топ-менеджер, менеджер видят все, глоб. исполнитель - только свои
if ($user->role !== User::ROLE_ADMIN && 
    !in_array($userGlobalRole, [GlobalProjectRole::ROLE_RECTOR, GlobalProjectRole::ROLE_GLOBAL_TOP_MANAGER, GlobalProjectRole::ROLE_GLOBAL_MANAGER])) {
    // Пользователь видит только задачи, на которые он назначен
    $allTasks = Task::find()
        ->where(['project_id' => $model->_id])
        ->andWhere(['$or' => [
            ['is_archived' => false],
            ['is_archived' => ['$exists' => false]],
        ]])
        ->all();
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
                        <i class="fas fa-globe me-2"></i>
                        Глобальный проект для всего университета
                        <?php
                        $managers = $model->getManagers();
                        if (!empty($managers)):
                        ?>
                            <span class="mx-2">•</span>
                            <i class="fas fa-user-tie me-2"></i>
                            <?php
                            $managerNames = [];
                            foreach ($managers as $manager) {
                                $managerNames[] = Html::encode($manager->fio);
                            }
                            echo implode(', ', $managerNames);
                            ?>
                        <?php else: ?>
                            <span class="mx-2">•</span>
                            <i class="fas fa-user-tie me-2"></i>
                            Без руководителей
                        <?php endif; ?>
                    </p>
                </div>
                <div class="text-end">
                    <?php if ($canEdit): ?>
                        <div class="d-flex gap-2">
                            <?= Html::a(
                                '<i class="fas fa-edit me-2"></i>Редактировать',
                                ['update', 'id' => (string)$model->_id],
                                ['class' => 'nku-btn nku-btn--primary']
                            ) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
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
            <button class="nav-link" id="tasks-tab" data-bs-toggle="tab" data-bs-target="#tasks" 
                    type="button" role="tab" aria-controls="tasks" aria-selected="false">
                <i class="fas fa-tasks me-2"></i>
                Задачи
                <span class="badge bg-primary ms-2"><?= $tasksCount ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <?= Html::a(
                '<i class="fas fa-road me-2"></i>Дорожная карта',
                ['/global-roadmap/view'],
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

                    <div class="nku-card mt-4">
                        <div class="nku-card__header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Документы проекта</h5>
                            <?php if ($canManageDocuments): ?>
                                <span class="nku-badge nku-badge--primary">
                                    <i class="fas fa-upload"></i>
                                    Можно загрузить
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="nku-card__body">
                            <?php if (!empty($documents)): ?>
                                <div class="nku-docs__list">
                                    <?php foreach ($documents as $doc): ?>
                                        <?php
                                        $fileName = (string)($doc->file_name ?: 'Документ');
                                        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                                        $icon = $ext === 'pdf' ? 'fa-file-pdf' : 'fa-file-word';
                                        $iconMod = $ext === 'pdf' ? 'nku-docs__icon--pdf' : 'nku-docs__icon--word';

                                        $size = (int)($doc->file_size ?? 0);
                                        $sizeLabel = $size >= 1024 * 1024
                                            ? number_format($size / (1024 * 1024), 2, '.', ' ') . ' МБ'
                                            : number_format($size / 1024, 1, '.', ' ') . ' КБ';
                                        $createdTs = ($doc->created_at instanceof \MongoDB\BSON\UTCDateTime)
                                            ? $doc->created_at->toDateTime()->getTimestamp()
                                            : null;
                                        $dateLabel = $createdTs ? date('d.m.Y H:i', $createdTs) : '-';
                                        ?>
                                        <div class="nku-docs__item">
                                            <div class="nku-docs__top">
                                                <div class="nku-docs__icon <?= $iconMod ?>">
                                                    <i class="fas <?= $icon ?>"></i>
                                                </div>
                                                <?php if ($canManageDocuments): ?>
                                                    <div class="nku-docs__actions">
                                                        <?= Html::a(
                                                            '<i class="fas fa-trash"></i>',
                                                            ['project/delete-document', 'id' => (string)$doc->_id],
                                                            [
                                                                'class' => 'nku-btn nku-btn--sm nku-btn--danger',
                                                                'title' => 'Удалить',
                                                                'data' => [
                                                                    'confirm' => 'Удалить документ? Его можно будет загрузить снова.',
                                                                    'method' => 'post',
                                                                ],
                                                            ]
                                                        ) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="nku-docs__content">
                                                <?= Html::a(
                                                    Html::encode($fileName),
                                                    ['project/download-document', 'id' => (string)$doc->_id],
                                                    ['target' => '_blank', 'class' => 'nku-docs__name']
                                                ) ?>
                                                <div class="nku-docs__meta">
                                                    <?= Html::encode($sizeLabel) ?> • <?= Html::encode($dateLabel) ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="nku-empty nku-docs__empty">
                                    <div class="nku-empty__icon">
                                        <i class="fas fa-file-alt"></i>
                                    </div>
                                    <div class="nku-empty__title">Документы не загружены</div>
                                    <div class="nku-empty__description">
                                        Здесь будут храниться дорожная карта, паспорт проекта и другие официальные документы.
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($canManageDocuments): ?>
                                <form method="post" enctype="multipart/form-data"
                                      action="<?= Url::to(['project/upload-documents', 'id' => (string)$model->_id]) ?>">
                                    <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->getCsrfToken()) ?>
                                    <div class="nku-dropzone" id="global-project-docs-dropzone">
                                        <input type="file"
                                               id="global-project-docs-input"
                                               name="documents[]"
                                               class="nku-dropzone__input"
                                               multiple
                                               accept=".pdf,.doc,.docx">
                                        <div class="nku-dropzone__left">
                                            <div class="nku-dropzone__title">Загрузка документов</div>
                                            <div class="nku-dropzone__hint">
                                                Перетащите файлы сюда или выберите (PDF / DOC / DOCX). Макс. 15 МБ на файл.
                                            </div>
                                            <div class="nku-dropzone__files" id="global-project-docs-files" style="display:none;"></div>
                                        </div>
                                        <div class="nku-dropzone__right">
                                            <label for="global-project-docs-input" class="nku-btn nku-btn--secondary">
                                                <i class="fas fa-folder-open me-2"></i>Выбрать файлы
                                            </label>
                                            <button type="submit" class="nku-btn nku-btn--primary nku-dropzone__submit" id="global-project-docs-submit">
                                                <i class="fas fa-upload me-2"></i>Загрузить
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
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

                        <div class="text-center d-flex gap-3 justify-content-center">
                            <?= Html::a(
                                '<i class="fas fa-list me-2"></i>Список задач',
                                ['global-project/tasks-list', 'id' => (string)$model->_id],
                                ['class' => 'nku-btn nku-btn--lg nku-btn--info']
                            ) ?>
                            <?= Html::a(
                                '<i class="fas fa-columns me-2"></i>Канбан-доска',
                                ['global-project/kanban', 'id' => (string)$model->_id],
                                ['class' => 'nku-btn nku-btn--lg nku-btn--primary']
                            ) ?>
                        </div>
                    <?php else: ?>
                        <div class="nku-empty">
                            <div class="nku-empty__icon">
                                <i class="fas fa-tasks"></i>
                            </div>
                            <div class="nku-empty__title">Задачи не созданы</div>
                            <div class="nku-empty__description">
                                Создайте первую задачу для глобального проекта
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

<script>
document.addEventListener('DOMContentLoaded', function() {
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

<?php if ($canManageDocuments): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var dropzone = document.getElementById('global-project-docs-dropzone');
    var input = document.getElementById('global-project-docs-input');
    var filesBox = document.getElementById('global-project-docs-files');
    var submitBtn = document.getElementById('global-project-docs-submit');

    if (!dropzone || !input || !filesBox || !submitBtn) return;

    function renderFiles(files) {
        filesBox.innerHTML = '';
        if (!files || files.length === 0) {
            filesBox.style.display = 'none';
            submitBtn.disabled = true;
            return;
        }

        Array.prototype.forEach.call(files, function (f) {
            var chip = document.createElement('span');
            chip.className = 'nku-dropzone__filechip';
            var sizeMb = f.size / (1024 * 1024);
            var sizeLabel = sizeMb >= 1 ? (sizeMb.toFixed(2) + ' МБ') : ((f.size / 1024).toFixed(1) + ' КБ');
            chip.textContent = f.name + ' • ' + sizeLabel;
            filesBox.appendChild(chip);
        });
        filesBox.style.display = 'flex';
        submitBtn.disabled = false;
    }

    input.addEventListener('change', function () {
        renderFiles(input.files);
    });

    dropzone.addEventListener('dragover', function (e) {
        e.preventDefault();
        dropzone.classList.add('is-dragover');
    });
    dropzone.addEventListener('dragleave', function () {
        dropzone.classList.remove('is-dragover');
    });
    dropzone.addEventListener('drop', function (e) {
        e.preventDefault();
        dropzone.classList.remove('is-dragover');
        if (!e.dataTransfer || !e.dataTransfer.files) return;

        try {
            input.files = e.dataTransfer.files;
        } catch (err) {
            // Some browsers restrict assigning files programmatically
        }
        renderFiles(input.files || e.dataTransfer.files);
    });

    // Initial state
    renderFiles(input.files);
});
</script>
<?php endif; ?>