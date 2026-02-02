<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\models\Direction;
use app\models\User;
use app\models\GlobalProjectRole;

/** @var yii\web\View $this */
/** @var app\models\Direction[] $directions */
/** @var bool $canCreate */
/** @var string|null $userGlobalRole */
/** @var app\models\Project[] $legacyProjects */

$this->title = 'Глобальный проект';
$this->params['breadcrumbs'][] = $this->title;

$user = Yii::$app->user->identity;
?>

<div class="direction-index">
    <!-- Header -->
    <div class="nku-card mb-4">
        <div class="nku-card__body">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="mb-2"><?= Html::encode($this->title) ?></h1>
                    <p class="text-muted mb-0">
                        <i class="fas fa-globe me-2"></i>
                        Направления глобального проекта университета
                    </p>
                </div>
                <?php if ($canCreate): ?>
                    <div>
                        <?= Html::a(
                            '<i class="fas fa-plus me-2"></i>Создать направление',
                            ['create'],
                            ['class' => 'nku-btn nku-btn--primary nku-btn--lg']
                        ) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Directions Grid -->
    <?php if (!empty($directions)): ?>
        <div class="row">
            <?php foreach ($directions as $direction): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="nku-card direction-card h-100">
                        <div class="nku-card__body">
                            <div class="direction-card__header mb-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <h4 class="direction-card__title mb-0">
                                        <?= Html::a(
                                            Html::encode($direction->title),
                                            ['view', 'id' => (string)$direction->_id],
                                            ['class' => 'text-decoration-none']
                                        ) ?>
                                    </h4>
                                    <span class="nku-badge nku-badge--status-<?= $direction->status ?>">
                                        <?= $direction->getStatusLabel() ?>
                                    </span>
                                </div>
                            </div>
                            
                            <?php if ($direction->description): ?>
                                <p class="direction-card__description text-muted mb-3">
                                    <?= Html::encode(mb_substr($direction->description, 0, 150)) ?>
                                    <?php if (mb_strlen($direction->description) > 150): ?>...<?php endif; ?>
                                </p>
                            <?php endif; ?>
                            
                            <div class="direction-card__stats mb-3">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="stat-item">
                                            <i class="fas fa-project-diagram text-primary me-2"></i>
                                            <span class="stat-value"><?= $direction->getActiveProjectsCount() ?></span>
                                            <span class="stat-label">проектов</span>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="stat-item">
                                            <i class="fas fa-tasks text-success me-2"></i>
                                            <span class="stat-value"><?= $direction->getTasksCount() ?></span>
                                            <span class="stat-label">задач</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <?php 
                            $managers = $direction->getManagers();
                            if (!empty($managers)): 
                            ?>
                                <div class="direction-card__managers mb-3">
                                    <small class="text-muted d-block mb-1">Руководители:</small>
                                    <div class="managers-list">
                                        <?php 
                                        $displayManagers = array_slice($managers, 0, 3);
                                        foreach ($displayManagers as $manager): 
                                        ?>
                                            <span class="manager-badge">
                                                <i class="fas fa-user me-1"></i>
                                                <?= Html::encode($manager->fio) ?>
                                            </span>
                                        <?php endforeach; ?>
                                        <?php if (count($managers) > 3): ?>
                                            <span class="manager-badge manager-badge--more">
                                                +<?= count($managers) - 3 ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="direction-card__actions">
                                <?= Html::a(
                                    '<i class="fas fa-arrow-right me-2"></i>Перейти',
                                    ['view', 'id' => (string)$direction->_id],
                                    ['class' => 'nku-btn nku-btn--primary nku-btn--block']
                                ) ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="nku-card">
            <div class="nku-card__body">
                <div class="nku-empty">
                    <div class="nku-empty__icon">
                        <i class="fas fa-compass"></i>
                    </div>
                    <div class="nku-empty__title">Направления не созданы</div>
                    <div class="nku-empty__description">
                        Создайте первое направление для глобального проекта
                    </div>
                    <?php if ($canCreate): ?>
                        <div class="nku-empty__action">
                            <?= Html::a(
                                '<i class="fas fa-plus me-2"></i>Создать направление',
                                ['create'],
                                ['class' => 'nku-btn nku-btn--primary']
                            ) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Legacy Projects Section -->
    <?php if (!empty($legacyProjects) && $canCreate): ?>
        <div class="nku-card mt-4">
            <div class="nku-card__header">
                <h5 class="mb-0">
                    <i class="fas fa-history me-2 text-warning"></i>
                    Старые глобальные проекты (без направления)
                </h5>
            </div>
            <div class="nku-card__body">
                <p class="text-muted mb-3">
                    Эти проекты были созданы до введения системы направлений. Вы можете скрыть их или перенести в соответствующее направление.
                </p>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Название</th>
                                <th>Статус</th>
                                <th>Задач</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($legacyProjects as $project): ?>
                                <tr>
                                    <td>
                                        <?= Html::a(
                                            Html::encode($project->title),
                                            ['/project/view', 'id' => (string)$project->_id],
                                            ['class' => 'text-decoration-none']
                                        ) ?>
                                    </td>
                                    <td>
                                        <span class="nku-badge nku-badge--status-<?= $project->status ?>">
                                            <?= $project->getStatusLabel() ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $tasksCount = \app\models\Task::find()
                                            ->where(['project_id' => $project->_id])
                                            ->andWhere(['$or' => [
                                                ['is_archived' => false],
                                                ['is_archived' => ['$exists' => false]],
                                            ]])
                                            ->count();
                                        echo $tasksCount;
                                        ?>
                                    </td>
                                    <td>
                                        <?= Html::beginForm(['mark-legacy'], 'post', ['class' => 'd-inline']) ?>
                                            <?= Html::hiddenInput('project_id', (string)$project->_id) ?>
                                            <?= Html::submitButton(
                                                '<i class="fas fa-eye-slash"></i> Скрыть',
                                                [
                                                    'class' => 'nku-btn nku-btn--sm nku-btn--warning',
                                                    'data-confirm' => 'Вы уверены, что хотите скрыть этот проект?'
                                                ]
                                            ) ?>
                                        <?= Html::endForm() ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.direction-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border: 1px solid var(--nku-color-border);
}

.direction-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
}

.direction-card__title a {
    color: var(--nku-color-text);
}

.direction-card__title a:hover {
    color: var(--nku-color-primary);
}

.direction-card__description {
    font-size: 0.9rem;
    line-height: 1.5;
}

.stat-item {
    display: flex;
    align-items: center;
    font-size: 0.9rem;
}

.stat-value {
    font-weight: 600;
    margin-right: 0.25rem;
}

.stat-label {
    color: var(--nku-color-text-secondary);
}

.managers-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.manager-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.5rem;
    background: var(--nku-color-bg-secondary);
    border-radius: 4px;
    font-size: 0.8rem;
}

.manager-badge--more {
    background: var(--nku-color-primary-light);
    color: var(--nku-color-primary);
}

.nku-btn--block {
    display: block;
    width: 100%;
    text-align: center;
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

.nku-badge--status-draft {
    background-color: #6c757d;
    color: white;
}

.nku-badge--status-active {
    background-color: #28a745;
    color: white;
}

.nku-badge--status-archived {
    background-color: #ffc107;
    color: #212529;
}
</style>
