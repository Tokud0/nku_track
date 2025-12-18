<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use app\models\Project;
use app\models\ProjectSpec;
use app\models\Task;
use app\models\User;

/** @var yii\web\View $this */
/** @var app\models\Project $model */

$this->title = $model->title;
$this->params['breadcrumbs'][] = ['label' => 'Проекты', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$user = Yii::$app->user->identity;
// ТЗ передается из контроллера
?>
<div class="project-view">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= Html::encode($this->title) ?></h1>
        <span class="badge badge-<?= [
            Project::STATUS_DRAFT => 'secondary',
            Project::STATUS_ACTIVE => 'success',
            Project::STATUS_REVIEW => 'warning',
            Project::STATUS_FINISHED => 'info',
            Project::STATUS_FROZEN => 'danger',
        ][$model->status] ?? 'secondary' ?> badge-lg">
            <?= $model->getStatusLabel() ?>
        </span>
    </div>

    <div class="mb-3">
        <?php 
        // Админ может редактировать все проекты
        // Ректор может редактировать все проекты своего подразделения
        // Топ-менеджер и менеджер могут редактировать проекты своего подразделения
        $canEditProject = false;
        if ($user->role === User::ROLE_ADMIN) {
            $canEditProject = true;
        } elseif ($user->role === User::ROLE_RECTOR && 
                  $model->department_id && $user->department_id &&
                  (string)$model->department_id === (string)$user->department_id) {
            $canEditProject = true;
        } elseif (in_array($user->role, [User::ROLE_TOP_MANAGER, User::ROLE_MANAGER]) &&
                  $model->department_id && $user->department_id &&
                  (string)$model->department_id === (string)$user->department_id) {
            $canEditProject = true;
        }
        
        if ($canEditProject): ?>
            <?= Html::a('Редактировать', ['update', 'id' => (string)$model->_id], ['class' => 'btn btn-primary']) ?>
            <?= Html::a('Удалить', ['delete', 'id' => (string)$model->_id], [
                'class' => 'btn btn-danger',
                'data' => [
                    'confirm' => 'Вы уверены, что хотите удалить этот проект?',
                    'method' => 'post',
                ],
            ]) ?>
        <?php endif; ?>
        <?= Html::a('Назад к списку', ['index'], ['class' => 'btn btn-secondary']) ?>
    </div>

    <div class="row">
        <!-- Основная информация о проекте -->
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="mb-0">Информация о проекте</h4>
                </div>
                <div class="card-body">
                    <?= DetailView::widget([
                        'model' => $model,
                        'options' => ['class' => 'table table-borderless'],
                        'attributes' => [
                            'title',
                            [
                                'attribute' => 'description',
                                'format' => 'raw',
                                'value' => nl2br(Html::encode($model->description)),
                            ],
                            [
                                'attribute' => 'goals',
                                'format' => 'raw',
                                'value' => nl2br(Html::encode($model->goals)),
                            ],
                            [
                                'attribute' => 'start_date',
                                'value' => function($model) {
                                    if ($model->start_date instanceof \MongoDB\BSON\UTCDateTime) {
                                        return date('d.m.Y', $model->start_date->toDateTime()->getTimestamp());
                                    }
                                    return '-';
                                },
                            ],
                            [
                                'attribute' => 'end_date',
                                'value' => function($model) {
                                    if ($model->end_date instanceof \MongoDB\BSON\UTCDateTime) {
                                        return date('d.m.Y', $model->end_date->toDateTime()->getTimestamp());
                                    }
                                    return '-';
                                },
                            ],
                            [
                                'attribute' => 'manager_id',
                                'value' => $model->manager ? $model->manager->fio : '-',
                            ],
                            [
                                'attribute' => 'department_id',
                                'value' => $model->department ? $model->department->name : '-',
                            ],
                            [
                                'attribute' => 'progress',
                                'format' => 'raw',
                                'value' => function($model) {
                                    return '<div class="progress" style="height: 25px;">
                                        <div class="progress-bar ' . ($model->progress >= 100 ? 'bg-success' : ($model->progress >= 50 ? 'bg-info' : 'bg-warning')) . '" 
                                             role="progressbar" 
                                             style="width: ' . $model->progress . '%"
                                             aria-valuenow="' . $model->progress . '" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                            ' . $model->progress . '%
                                        </div>
                                    </div>';
                                },
                            ],
                            [
                                'attribute' => 'next_report_deadline',
                                'value' => function($model) {
                                    if ($model->next_report_deadline instanceof \MongoDB\BSON\UTCDateTime) {
                                        $deadline = $model->next_report_deadline->toDateTime()->getTimestamp();
                                        $now = time();
                                        $daysLeft = floor(($deadline - $now) / (24 * 60 * 60));
                                        
                                        $class = $daysLeft < 0 ? 'text-danger' : ($daysLeft <= 3 ? 'text-warning' : 'text-success');
                                        return '<span class="' . $class . '">' . date('d.m.Y H:i', $deadline) . ' (' . $daysLeft . ' дн.)</span>';
                                    }
                                    return '<span class="text-muted">Не установлен</span>';
                                },
                                'format' => 'raw',
                            ],
                            [
                                'attribute' => 'created_at',
                                'value' => function($model) {
                                    if ($model->created_at instanceof \MongoDB\BSON\UTCDateTime) {
                                        return date('d.m.Y H:i', $model->created_at->toDateTime()->getTimestamp());
                                    }
                                    return '-';
                                },
                            ],
                        ],
                    ]) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Техническое задание -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Техническое задание</h4>
                    <?php 
                    // Топ-менеджер, ректор (создатель проекта) и админ могут работать с ТЗ
                    $canWorkWithSpec = $user->role === User::ROLE_ADMIN || 
                                      $user->role === User::ROLE_TOP_MANAGER ||
                                      ($user->role === User::ROLE_RECTOR && (string)$model->manager_id === (string)$user->_id);
                    if ($canWorkWithSpec): ?>
                        <?php if ($spec): ?>
                            <?php if ($model->status === Project::STATUS_DRAFT): ?>
                                <?= Html::a('Редактировать ТЗ', ['project-spec/update', 'project_id' => (string)$model->_id], ['class' => 'btn btn-sm btn-primary']) ?>
                            <?php endif; ?>
                        <?php else: ?>
                            <?= Html::a('Добавить ТЗ', ['project-spec/create', 'project_id' => (string)$model->_id], ['class' => 'btn btn-sm btn-success']) ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if ($spec): ?>
                        <div class="spec-content">
                            <div class="mb-3">
                                <h5>Описание ТЗ</h5>
                                <div class="p-3 bg-light rounded">
                                    <?= nl2br(Html::encode($spec->tz_text)) ?>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <h5>Период отчетности</h5>
                                    <p><?= $spec->getReportPeriodLabel() ?></p>
                                    <?php if ($spec->report_period === ProjectSpec::PERIOD_CUSTOM && $spec->custom_period_days): ?>
                                        <p><small class="text-muted">Каждые <?= $spec->custom_period_days ?> дней</small></p>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <h5>Дата создания ТЗ</h5>
                                    <p>
                                        <?php if ($spec->created_at instanceof \MongoDB\BSON\UTCDateTime): ?>
                                            <?= date('d.m.Y H:i', $spec->created_at->toDateTime()->getTimestamp()) ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>

                            <?php if (!empty($spec->milestones)): ?>
                                <div class="mb-3">
                                    <h5>Этапы проекта</h5>
                                    <ul class="list-group">
                                        <?php foreach ($spec->milestones as $milestone): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <span>
                                                    <?php if (isset($milestone['done']) && $milestone['done']): ?>
                                                        <span class="badge badge-success mr-2">✓</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-secondary mr-2">○</span>
                                                    <?php endif; ?>
                                                    <?= Html::encode($milestone['name'] ?? 'Без названия') ?>
                                                </span>
                                                <?php if (isset($milestone['deadline']) && $milestone['deadline']): ?>
                                                    <small class="text-muted">До: <?= Html::encode($milestone['deadline']) ?></small>
                                                <?php endif; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($spec->metrics)): ?>
                                <div class="mb-3">
                                    <h5>Метрики успеха</h5>
                                    <ul class="list-group">
                                        <?php foreach ($spec->metrics as $metric): ?>
                                            <li class="list-group-item"><?= Html::encode($metric) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($spec->report_template)): ?>
                                <div class="mb-3">
                                    <h5>Шаблон отчета</h5>
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Поле</th>
                                                    <th>Описание</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($spec->report_template as $key => $value): ?>
                                                    <tr>
                                                        <td><strong><?= Html::encode($key) ?></strong></td>
                                                        <td><?= Html::encode($value) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <p>Техническое задание еще не создано.</p>
                            <?php if (($user->role === User::ROLE_MANAGER && (string)$model->manager_id === (string)$user->_id) || $user->role === User::ROLE_ADMIN): ?>
                                <?= Html::a('Создать ТЗ', ['project-spec/create', 'project_id' => (string)$model->_id], ['class' => 'btn btn-success']) ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Ссылка на канбан-доску -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Задачи проекта</h4>
                    <div>
                        <?php 
                        // Менеджер, топ-менеджер, ректор и админ могут создавать задачи
                        $canCreateTask = in_array($user->role, [User::ROLE_MANAGER, User::ROLE_TOP_MANAGER, User::ROLE_RECTOR, User::ROLE_ADMIN]);
                        if ($canCreateTask): ?>
                            <?= Html::a('Создать задачу', ['task/create', 'project_id' => (string)$model->_id], ['class' => 'btn btn-sm btn-success mr-2']) ?>
                        <?php endif; ?>
                        <?= Html::a('Канбан-доска', ['kanban', 'id' => (string)$model->_id], ['class' => 'btn btn-sm btn-primary']) ?>
                    </div>
                </div>
                <div class="card-body">
                    <?php
                    $tasks = Task::find()->where(['project_id' => $model->_id])->all();
                    $tasksCount = count($tasks);
                    ?>
                    <p class="mb-0">
                        Всего задач: <strong><?= $tasksCount ?></strong>
                        <?php if ($tasksCount > 0): ?>
                            | <?= Html::a('Перейти к канбан-доске', ['kanban', 'id' => (string)$model->_id], ['class' => 'btn btn-sm btn-outline-primary']) ?>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

</div>

<style>
.project-view .card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid rgba(0, 0, 0, 0.125);
}

.project-view .card-header {
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
}

.project-view .badge-lg {
    font-size: 1rem;
    padding: 0.5rem 1rem;
}

.project-view .spec-content h5 {
    color: #495057;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #e9ecef;
}
</style>
