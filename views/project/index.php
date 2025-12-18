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

// Статусы с цветами
$statusColors = [
    Project::STATUS_DRAFT => 'secondary',
    Project::STATUS_ACTIVE => 'success',
    Project::STATUS_REVIEW => 'warning',
    Project::STATUS_FINISHED => 'info',
    Project::STATUS_FROZEN => 'danger',
];

$statusLabels = [
    Project::STATUS_DRAFT => 'Черновик',
    Project::STATUS_ACTIVE => 'Активный',
    Project::STATUS_REVIEW => 'На проверке',
    Project::STATUS_FINISHED => 'Завершен',
    Project::STATUS_FROZEN => 'Заморожен',
];
?>
<div class="project-index">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= Html::encode($this->title) ?></h1>
        <?php if ($user->role === User::ROLE_RECTOR || $user->role === User::ROLE_ADMIN): ?>
            <?= Html::a('Создать проект', ['create'], ['class' => 'btn btn-success']) ?>
        <?php endif; ?>
    </div>

    <!-- Форма поиска -->
    <div class="card mb-4">
        <div class="card-body">
            <?php $form = \yii\widgets\ActiveForm::begin([
                'action' => ['index'],
                'method' => 'get',
                'options' => ['class' => 'form-inline'],
            ]); ?>
            
            <?= $form->field($searchModel, 'title')->textInput(['placeholder' => 'Название проекта', 'class' => 'form-control mr-2'])->label(false) ?>
            
            <?= $form->field($searchModel, 'status')->dropDownList([
                '' => 'Все статусы',
                Project::STATUS_DRAFT => 'Черновик',
                Project::STATUS_ACTIVE => 'Активный',
                Project::STATUS_REVIEW => 'На проверке',
                Project::STATUS_FINISHED => 'Завершен',
                Project::STATUS_FROZEN => 'Заморожен',
            ], ['class' => 'form-control mr-2'])->label(false) ?>
            
            <?php
            $departments = \app\models\Department::find()->all();
            $departmentList = [];
            foreach ($departments as $dept) {
                $departmentList[(string)$dept->_id] = $dept->name;
            }
            ?>
            <?= $form->field($searchModel, 'department_id')->dropDownList(
                $departmentList,
                ['prompt' => 'Все подразделения', 'class' => 'form-control mr-2']
            )->label(false) ?>
            
            <?= Html::submitButton('Поиск', ['class' => 'btn btn-primary mr-2']) ?>
            <?= Html::a('Сбросить', ['index'], ['class' => 'btn btn-secondary']) ?>
            
            <?php \yii\widgets\ActiveForm::end(); ?>
        </div>
    </div>

    <?php Pjax::begin(); ?>

    <?php if (empty($projects)): ?>
        <div class="alert alert-info">
            Проекты не найдены.
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($projects as $project): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm project-card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><?= Html::encode($project->title) ?></h5>
                            <span class="badge badge-<?= $statusColors[$project->status] ?? 'secondary' ?>">
                                <?= $statusLabels[$project->status] ?? $project->status ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <?php if ($project->description): ?>
                                <p class="card-text text-muted">
                                    <?= Html::encode(mb_substr($project->description, 0, 100)) ?>
                                    <?= mb_strlen($project->description) > 100 ? '...' : '' ?>
                                </p>
                            <?php endif; ?>
                            
                            <div class="mb-2">
                                <small class="text-muted">
                                    <strong>Руководитель:</strong> <?= $project->manager ? Html::encode($project->manager->fio) : '-' ?>
                                </small>
                            </div>
                            
                            <?php if ($project->department): ?>
                                <div class="mb-2">
                                    <small class="text-muted">
                                        <strong>Подразделение:</strong> <?= Html::encode($project->department->name) ?>
                                    </small>
                                </div>
                            <?php endif; ?>
                            
                            <div class="mb-2">
                                <small class="text-muted">
                                    <strong>Прогресс:</strong>
                                </small>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar <?= $project->progress >= 100 ? 'bg-success' : ($project->progress >= 50 ? 'bg-info' : 'bg-warning') ?>" 
                                         role="progressbar" 
                                         style="width: <?= $project->progress ?>%"
                                         aria-valuenow="<?= $project->progress ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                        <?= $project->progress ?>%
                                    </div>
                                </div>
                            </div>
                            
                            <?php if ($project->start_date instanceof \MongoDB\BSON\UTCDateTime): ?>
                                <div class="mb-2">
                                    <small class="text-muted">
                                        <strong>Начало:</strong> <?= date('d.m.Y', $project->start_date->toDateTime()->getTimestamp()) ?>
                                    </small>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($project->end_date instanceof \MongoDB\BSON\UTCDateTime): ?>
                                <div class="mb-2">
                                    <small class="text-muted">
                                        <strong>Окончание:</strong> <?= date('d.m.Y', $project->end_date->toDateTime()->getTimestamp()) ?>
                                    </small>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($project->department): ?>
                                <div class="mb-2">
                                    <small class="text-muted">
                                        <strong>Подразделение:</strong> <?= Html::encode($project->department->name) ?>
                                    </small>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer bg-transparent">
                            <div class="btn-group w-100" role="group">
                                <?= Html::a('Просмотр', ['view', 'id' => (string)$project->_id], ['class' => 'btn btn-sm btn-info']) ?>
                                <?php 
                                $canEditProjectInList = false;
                                if ($user->role === User::ROLE_ADMIN) {
                                    $canEditProjectInList = true;
                                } elseif ($user->role === User::ROLE_RECTOR && 
                                          $project->department_id && $user->department_id &&
                                          (string)$project->department_id === (string)$user->department_id) {
                                    $canEditProjectInList = true;
                                } elseif (in_array($user->role, [User::ROLE_TOP_MANAGER, User::ROLE_MANAGER]) &&
                                          $project->department_id && $user->department_id &&
                                          (string)$project->department_id === (string)$user->department_id) {
                                    $canEditProjectInList = true;
                                }
                                if ($canEditProjectInList): ?>
                                    <?= Html::a('Редактировать', ['update', 'id' => (string)$project->_id], ['class' => 'btn btn-sm btn-primary']) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Пагинация -->
        <?php if ($pagination && $pagination->pageCount > 1): ?>
            <div class="mt-4">
                <?= LinkPager::widget([
                    'pagination' => $pagination,
                    'options' => ['class' => 'pagination justify-content-center'],
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
    transition: transform 0.2s, box-shadow 0.2s;
    border: 1px solid #dee2e6;
}

.project-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.project-card .card-header {
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
}

.project-card .progress {
    border-radius: 10px;
}
</style>
