<?php

use yii\helpers\Html;
use yii\widgets\Pjax;
use yii\widgets\LinkPager;
use app\models\Project;
use app\models\User;

/** @var yii\web\View $this */
/** @var app\models\ProjectSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Проекты';
$this->params['breadcrumbs'][] = $this->title;

$user = Yii::$app->user->identity;
$projects = $dataProvider->getModels();
$pagination = $dataProvider->getPagination();
?>
<div class="project-index">

    <!-- Header с заголовком и CTA -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1">
                <i class="fas fa-project-diagram me-2"></i>
                <?= Html::encode($this->title) ?>
            </h1>
            <p class="text-muted mb-0">Управление проектами организации</p>
        </div>
        <?php if (in_array($user->role, [User::ROLE_HEAD, User::ROLE_TOP_MANAGER, User::ROLE_ADMIN]) && $user->role !== User::ROLE_RECTOR): ?>
            <?= Html::a(
                '<i class="fas fa-plus-circle me-2"></i>Создать проект', 
                ['create'], 
                ['class' => 'nku-btn nku-btn--primary nku-btn--lg']
            ) ?>
        <?php endif; ?>
    </div>

    <?php if (!$user->department_id && $user->role !== User::ROLE_ADMIN && $user->role !== User::ROLE_RECTOR): ?>
        <div class="alert alert-warning mb-4" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Внимание!</strong> Вы не прикреплены ни к одному подразделению. Обратитесь к администратору для прикрепления к подразделению.
        </div>
    <?php endif; ?>

    <!-- Filter Bar -->
    <div class="nku-card mb-4">
        <div class="nku-card__body">
            <?php $form = \yii\widgets\ActiveForm::begin([
                'action' => ['index'],
                'method' => 'get',
                'options' => ['class' => 'nku-filter-bar'],
            ]); ?>
            
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">
                        <i class="fas fa-search me-1"></i>
                        Поиск по названию
                    </label>
                    <?= $form->field($searchModel, 'title')->textInput([
                        'placeholder' => 'Введите название проекта',
                        'class' => 'form-control'
                    ])->label(false) ?>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label fw-semibold">
                        <i class="fas fa-tag me-1"></i>
                        Статус
                    </label>
                    <?= $form->field($searchModel, 'status')->dropDownList([
                        '' => 'Все статусы',
                        Project::STATUS_DRAFT => 'Черновик',
                        Project::STATUS_ACTIVE => 'Активный',
                        Project::STATUS_REVIEW => 'На проверке',
                        Project::STATUS_FINISHED => 'Завершен',
                        Project::STATUS_FROZEN => 'Заморожен',
                    ], ['class' => 'form-select'])->label(false) ?>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label fw-semibold">
                        <i class="fas fa-building me-1"></i>
                        Подразделение
                    </label>
                    <?php
                    $departments = \app\models\Department::find()->all();
                    $departmentList = [];
                    foreach ($departments as $dept) {
                        $departmentList[(string)$dept->_id] = $dept->name;
                    }
                    ?>
                    <?= $form->field($searchModel, 'department_id')->dropDownList(
                        $departmentList,
                        ['prompt' => 'Все подразделения', 'class' => 'form-select']
                    )->label(false) ?>
                </div>
                
                <div class="col-md-3">
                    <div class="d-flex gap-2">
                        <?= Html::submitButton(
                            '<i class="fas fa-search me-2"></i>Поиск', 
                            ['class' => 'nku-btn nku-btn--primary flex-grow-1']
                        ) ?>
                        <?= Html::a(
                            '<i class="fas fa-redo me-2"></i>Сбросить', 
                            ['index'], 
                            ['class' => 'nku-btn nku-btn--secondary']
                        ) ?>
                    </div>
                </div>
            </div>
            
            <?php \yii\widgets\ActiveForm::end(); ?>
        </div>
    </div>

    <?php Pjax::begin(); ?>

    <?php if (empty($projects)): ?>
        <div class="nku-empty">
            <div class="nku-empty__icon">
                <i class="fas fa-folder-open"></i>
            </div>
            <div class="nku-empty__title">Проекты не найдены</div>
            <div class="nku-empty__description">
                Попробуйте изменить параметры фильтрации или создайте новый проект
            </div>
            <?php if (in_array($user->role, [User::ROLE_HEAD, User::ROLE_TOP_MANAGER, User::ROLE_ADMIN]) && $user->role !== User::ROLE_RECTOR): ?>
                <div class="nku-empty__action">
                    <?= Html::a(
                        '<i class="fas fa-plus-circle me-2"></i>Создать проект',
                        ['create'],
                        ['class' => 'nku-btn nku-btn--primary']
                    ) ?>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <!-- Карточки проектов -->
        <div class="row">
            <?php foreach ($projects as $project): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="nku-card nku-card--hoverable h-100 project-card">
                        <!-- Header карточки -->
                        <div class="nku-card__header">
                            <div class="d-flex justify-content-between align-items-start">
                                <h5 class="mb-0 flex-grow-1 me-2">
                                    <?= Html::a(
                                        Html::encode($project->title), 
                                        ['view', 'id' => (string)$project->_id],
                                        ['class' => 'text-decoration-none text-dark']
                                    ) ?>
                                </h5>
                                <span class="nku-badge nku-badge--status-<?= $project->status ?>">
                                    <?= $project->getStatusLabel() ?>
                                </span>
                            </div>
                        </div>
                        
                        <!-- Body карточки -->
                        <div class="nku-card__body">
                            <?php if ($project->description): ?>
                                <p class="text-muted mb-3" style="font-size: 0.875rem; line-height: 1.5;">
                                    <?= Html::encode(mb_substr($project->description, 0, 100)) ?>
                                    <?= mb_strlen($project->description) > 100 ? '...' : '' ?>
                                </p>
                            <?php endif; ?>
                            
                            <!-- Прогресс -->
                            <!-- Временно скрыто по запросу пользователя -->
                            <?php if (false): ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <small class="text-muted fw-semibold">Прогресс</small>
                                    <small class="fw-bold" style="color: var(--nku-color-primary);">
                                        <?= $project->progress ?>%
                                    </small>
                                </div>
                                <div class="nku-progress">
                                    <div class="nku-progress__bar nku-progress__bar--<?= $project->progress >= 100 ? 'success' : ($project->progress >= 50 ? 'primary' : 'warning') ?>" 
                                         style="width: <?= $project->progress ?>%">
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Информация о проекте -->
                            <div class="project-meta">
                                <?php if ($project->manager): ?>
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-user-tie text-muted me-2" style="width: 20px;"></i>
                                        <small class="text-muted">
                                            <?= Html::encode($project->manager->fio) ?>
                                        </small>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($project->department): ?>
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-building text-muted me-2" style="width: 20px;"></i>
                                        <small class="text-muted">
                                            <?= Html::encode($project->department->name) ?>
                                        </small>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($project->next_report_deadline instanceof \MongoDB\BSON\UTCDateTime): ?>
                                    <?php 
                                    $deadline = $project->next_report_deadline->toDateTime()->getTimestamp();
                                    $now = time();
                                    $daysLeft = floor(($deadline - $now) / (24 * 60 * 60));
                                    $isOverdue = $daysLeft < 0;
                                    $isUrgent = $daysLeft <= 3 && !$isOverdue;
                                    ?>
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-clock text-muted me-2" style="width: 20px;"></i>
                                        <small class="<?= $isOverdue ? 'text-danger fw-bold' : ($isUrgent ? 'text-warning fw-bold' : 'text-muted') ?>">
                                            <?= date('d.m.Y', $deadline) ?>
                                            <?php if ($isOverdue): ?>
                                                (просрочен на <?= abs($daysLeft) ?> дн.)
                                            <?php elseif ($isUrgent): ?>
                                                (осталось <?= $daysLeft ?> дн.)
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Footer карточки -->
                        <div class="nku-card__footer">
                            <div class="d-flex gap-2">
                                <?= Html::a(
                                    '<i class="fas fa-eye me-2"></i>Просмотр',
                                    ['view', 'id' => (string)$project->_id],
                                    ['class' => 'nku-btn nku-btn--sm nku-btn--outline-primary flex-grow-1']
                                ) ?>
                                
                                <?php 
                                // Проверка прав на редактирование
                                $canEdit = false;
                                // Ректор может только просматривать, не может редактировать
                                if ($user->role === User::ROLE_RECTOR) {
                                    $canEdit = false;
                                } elseif ($user->role === User::ROLE_ADMIN) {
                                    $canEdit = true;
                                } elseif ($user->role === User::ROLE_HEAD) {
                                    // Руководитель может редактировать проекты своего подразделения
                                    if ($project->department_id && $user->department_id &&
                                        (string)$project->department_id === (string)$user->department_id) {
                                        $canEdit = true;
                                    }
                                } elseif ($user->role === User::ROLE_TOP_MANAGER) {
                                    // Топ-менеджер может редактировать проекты своего подразделения
                                    if ($project->department_id && $user->department_id &&
                                        (string)$project->department_id === (string)$user->department_id) {
                                        $canEdit = true;
                                    }
                                }
                                // Менеджер не может редактировать проекты, только задачи
                                
                                if ($canEdit): ?>
                                    <?= Html::a(
                                        '<i class="fas fa-edit me-2"></i>Изменить',
                                        ['update', 'id' => (string)$project->_id],
                                        ['class' => 'nku-btn nku-btn--sm nku-btn--outline-secondary']
                                    ) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Пагинация -->
        <?php if ($pagination && $pagination->pageCount > 1): ?>
            <div class="mt-4 d-flex justify-content-center">
                <?= LinkPager::widget([
                    'pagination' => $pagination,
                    'options' => ['class' => 'pagination'],
                    'linkOptions' => ['class' => 'page-link'],
                    'activePageCssClass' => 'active',
                    'disabledPageCssClass' => 'disabled',
                ]) ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php Pjax::end(); ?>

</div>

<style>
.project-card {
    display: flex;
    flex-direction: column;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.project-card:hover {
    transform: translateY(-4px);
}

.nku-filter-bar .row {
    margin: 0;
}

.project-meta i {
    min-width: 20px;
}
</style>
