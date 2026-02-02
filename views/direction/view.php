<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\models\Direction;
use app\models\Project;
use app\models\Task;
use app\models\User;
use app\models\GlobalProjectRole;

/** @var yii\web\View $this */
/** @var app\models\Direction $model */
/** @var app\models\Project[] $projects */
/** @var bool $canManage */
/** @var bool $canCreateProject */
/** @var string|null $userGlobalRole */

$this->title = $model->title;
$this->params['breadcrumbs'][] = ['label' => 'Глобальный проект', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$user = Yii::$app->user->identity;
?>

<div class="direction-view">
    <!-- Header -->
    <div class="nku-card mb-4">
        <div class="nku-card__body">
            <div class="d-flex justify-content-between align-items-start">
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <h1 class="mb-0"><?= Html::encode($this->title) ?></h1>
                        <span class="nku-badge nku-badge--lg nku-badge--status-<?= $model->status ?>">
                            <?= $model->getStatusLabel() ?>
                        </span>
                    </div>
                    <p class="text-muted mb-0">
                        <i class="fas fa-compass me-2"></i>
                        Направление глобального проекта
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
                        <?php endif; ?>
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <?= Html::a(
                        '<i class="fas fa-arrow-left me-2"></i>Назад',
                        ['index'],
                        ['class' => 'nku-btn nku-btn--secondary']
                    ) ?>
                    <?php if ($canManage): ?>
                        <?= Html::a(
                            '<i class="fas fa-edit me-2"></i>Редактировать',
                            ['update', 'id' => (string)$model->_id],
                            ['class' => 'nku-btn nku-btn--primary']
                        ) ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Description -->
    <?php if ($model->description): ?>
        <div class="nku-card mb-4">
            <div class="nku-card__header">
                <h5 class="mb-0">Описание направления</h5>
            </div>
            <div class="nku-card__body">
                <div class="direction-description">
                    <?= nl2br(Html::encode($model->description)) ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Projects Section -->
    <div class="nku-card">
        <div class="nku-card__header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-project-diagram me-2"></i>
                Проекты направления
                <span class="badge bg-primary ms-2"><?= count($projects) ?></span>
            </h5>
            <?php if ($canCreateProject): ?>
                <?= Html::a(
                    '<i class="fas fa-plus me-2"></i>Создать проект',
                    ['create-project', 'id' => (string)$model->_id],
                    ['class' => 'nku-btn nku-btn--success']
                ) ?>
            <?php endif; ?>
        </div>
        <div class="nku-card__body">
            <?php if (!empty($projects)): ?>
                <div class="row">
                    <?php foreach ($projects as $project): ?>
                        <?php
                        // Получаем статистику задач проекта
                        $tasks = Task::find()
                            ->where(['project_id' => $project->_id])
                            ->andWhere(['$or' => [
                                ['is_archived' => false],
                                ['is_archived' => ['$exists' => false]],
                            ]])
                            ->all();
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
                        $completedPercent = $tasksCount > 0 ? round(($tasksByStatus[Task::STATUS_DONE] / $tasksCount) * 100) : 0;
                        ?>
                        <div class="col-lg-6 col-xl-4 mb-4">
                            <div class="project-card h-100">
                                <div class="project-card__header">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="project-card__title mb-0">
                                            <?= Html::a(
                                                Html::encode($project->title),
                                                ['/project/view', 'id' => (string)$project->_id],
                                                ['class' => 'text-decoration-none']
                                            ) ?>
                                        </h5>
                                        <span class="nku-badge nku-badge--sm nku-badge--status-<?= $project->status ?>">
                                            <?= $project->getStatusLabel() ?>
                                        </span>
                                    </div>
                                    <?php if ($project->description): ?>
                                        <p class="project-card__description text-muted mb-0">
                                            <?= Html::encode(mb_substr($project->description, 0, 100)) ?>
                                            <?php if (mb_strlen($project->description) > 100): ?>...<?php endif; ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="project-card__body">
                                    <!-- Progress Bar -->
                                    <div class="progress-section mb-3">
                                        <div class="d-flex justify-content-between mb-1">
                                            <small class="text-muted">Прогресс</small>
                                            <small class="fw-semibold"><?= $completedPercent ?>%</small>
                                        </div>
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar bg-success" role="progressbar" 
                                                 style="width: <?= $completedPercent ?>%;" 
                                                 aria-valuenow="<?= $completedPercent ?>" 
                                                 aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                    
                                    <!-- Tasks Stats -->
                                    <div class="tasks-stats">
                                        <div class="row g-2 text-center">
                                            <div class="col-3">
                                                <div class="stat-mini stat-mini--todo">
                                                    <div class="stat-mini__value"><?= $tasksByStatus[Task::STATUS_TODO] ?></div>
                                                    <div class="stat-mini__label">К выпол.</div>
                                                </div>
                                            </div>
                                            <div class="col-3">
                                                <div class="stat-mini stat-mini--progress">
                                                    <div class="stat-mini__value"><?= $tasksByStatus[Task::STATUS_IN_PROGRESS] ?></div>
                                                    <div class="stat-mini__label">В работе</div>
                                                </div>
                                            </div>
                                            <div class="col-3">
                                                <div class="stat-mini stat-mini--review">
                                                    <div class="stat-mini__value"><?= $tasksByStatus[Task::STATUS_REVIEW] ?></div>
                                                    <div class="stat-mini__label">Проверка</div>
                                                </div>
                                            </div>
                                            <div class="col-3">
                                                <div class="stat-mini stat-mini--done">
                                                    <div class="stat-mini__value"><?= $tasksByStatus[Task::STATUS_DONE] ?></div>
                                                    <div class="stat-mini__label">Готово</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="project-card__footer">
                                    <div class="d-flex gap-2">
                                        <?= Html::a(
                                            '<i class="fas fa-eye me-1"></i>Просмотр',
                                            ['/project/view', 'id' => (string)$project->_id],
                                            ['class' => 'nku-btn nku-btn--sm nku-btn--primary flex-grow-1']
                                        ) ?>
                                        <?= Html::a(
                                            '<i class="fas fa-columns me-1"></i>Канбан',
                                            ['/project/kanban', 'id' => (string)$project->_id],
                                            ['class' => 'nku-btn nku-btn--sm nku-btn--secondary flex-grow-1']
                                        ) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="nku-empty">
                    <div class="nku-empty__icon">
                        <i class="fas fa-project-diagram"></i>
                    </div>
                    <div class="nku-empty__title">Проекты не созданы</div>
                    <div class="nku-empty__description">
                        Создайте первый проект в этом направлении
                    </div>
                    <?php if ($canCreateProject): ?>
                        <div class="nku-empty__action">
                            <?= Html::a(
                                '<i class="fas fa-plus me-2"></i>Создать проект',
                                ['create-project', 'id' => (string)$model->_id],
                                ['class' => 'nku-btn nku-btn--primary']
                            ) ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.direction-description {
    line-height: 1.7;
}

.project-card {
    background: var(--nku-color-bg);
    border: 1px solid var(--nku-color-border);
    border-radius: 8px;
    display: flex;
    flex-direction: column;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.project-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.project-card__header {
    padding: 1rem;
    border-bottom: 1px solid var(--nku-color-border);
}

.project-card__title a {
    color: var(--nku-color-text);
}

.project-card__title a:hover {
    color: var(--nku-color-primary);
}

.project-card__description {
    font-size: 0.85rem;
    line-height: 1.4;
}

.project-card__body {
    padding: 1rem;
    flex-grow: 1;
}

.project-card__footer {
    padding: 1rem;
    border-top: 1px solid var(--nku-color-border);
}

.stat-mini {
    padding: 0.5rem 0.25rem;
    border-radius: 4px;
}

.stat-mini__value {
    font-weight: 600;
    font-size: 1.1rem;
}

.stat-mini__label {
    font-size: 0.65rem;
    color: var(--nku-color-text-secondary);
    text-transform: uppercase;
}

.stat-mini--todo {
    background: rgba(108, 117, 125, 0.1);
}

.stat-mini--progress {
    background: rgba(13, 110, 253, 0.1);
}

.stat-mini--progress .stat-mini__value {
    color: #0d6efd;
}

.stat-mini--review {
    background: rgba(255, 193, 7, 0.1);
}

.stat-mini--review .stat-mini__value {
    color: #cc9a06;
}

.stat-mini--done {
    background: rgba(25, 135, 84, 0.1);
}

.stat-mini--done .stat-mini__value {
    color: #198754;
}

.nku-badge--lg {
    font-size: 0.9rem;
    padding: 0.35rem 0.75rem;
}

.nku-badge--sm {
    font-size: 0.7rem;
    padding: 0.2rem 0.5rem;
}

.nku-badge--status-draft {
    background-color: #6c757d;
    color: white;
}

.nku-badge--status-active {
    background-color: #28a745;
    color: white;
}

.nku-badge--status-review {
    background-color: #ffc107;
    color: #212529;
}

.nku-badge--status-finished {
    background-color: #17a2b8;
    color: white;
}

.nku-badge--status-frozen {
    background-color: #6c757d;
    color: white;
}

.nku-empty {
    text-align: center;
    padding: 3rem 1rem;
}

.nku-empty__icon {
    font-size: 3rem;
    color: var(--nku-color-text-secondary);
    margin-bottom: 1rem;
}

.nku-empty__title {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.nku-empty__description {
    color: var(--nku-color-text-secondary);
    margin-bottom: 1.5rem;
}
</style>
