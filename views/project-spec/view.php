<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use app\models\Project;
use app\models\ProjectSpec;
use app\models\User;

/** @var yii\web\View $this */
/** @var app\models\ProjectSpec $model */
/** @var app\models\Project $project */

$this->title = 'Техническое задание';
$this->params['breadcrumbs'][] = ['label' => 'Проекты', 'url' => ['project/index']];
$this->params['breadcrumbs'][] = ['label' => $project->title, 'url' => ['project/view', 'id' => (string)$project->_id]];
$this->params['breadcrumbs'][] = $this->title;

$user = Yii::$app->user->identity;
?>
<div class="project-spec-view">

    <h1><?= Html::encode($this->title) ?></h1>
    <h3>Проект: <?= Html::encode($project->title) ?></h3>

    <p>
        <?= Html::a('Назад к проекту', ['project/view', 'id' => (string)$project->_id], ['class' => 'btn btn-secondary']) ?>
        <?php if ($user->role === User::ROLE_MANAGER && (string)$project->manager_id === (string)$user->_id && $project->status === Project::STATUS_DRAFT): ?>
            <?= Html::a('Редактировать ТЗ', ['update', 'project_id' => (string)$project->_id], ['class' => 'btn btn-primary']) ?>
        <?php endif; ?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            [
                'attribute' => 'tz_text',
                'format' => 'raw',
                'value' => nl2br(Html::encode($model->tz_text)),
            ],
            [
                'attribute' => 'report_period',
                'value' => $model->getReportPeriodLabel(),
            ],
            [
                'attribute' => 'custom_period_days',
                'value' => $model->report_period === ProjectSpec::PERIOD_CUSTOM ? $model->custom_period_days . ' дней' : '-',
                'visible' => $model->report_period === ProjectSpec::PERIOD_CUSTOM,
            ],
            [
                'attribute' => 'milestones',
                'format' => 'raw',
                'value' => function($model) {
                    if (empty($model->milestones)) {
                        return 'Этапы не заданы';
                    }
                    $html = '<ul>';
                    foreach ($model->milestones as $milestone) {
                        $status = isset($milestone['done']) && $milestone['done'] ? '✓' : '○';
                        $deadline = isset($milestone['deadline']) ? ' (до ' . $milestone['deadline'] . ')' : '';
                        $html .= '<li>' . $status . ' ' . Html::encode($milestone['name']) . $deadline . '</li>';
                    }
                    $html .= '</ul>';
                    return $html;
                },
            ],
            [
                'attribute' => 'metrics',
                'format' => 'raw',
                'value' => function($model) {
                    if (empty($model->metrics)) {
                        return 'Метрики не заданы';
                    }
                    return '<ul><li>' . implode('</li><li>', array_map(function($metric) {
                        return Html::encode($metric);
                    }, $model->metrics)) . '</li></ul>';
                },
            ],
            [
                'attribute' => 'report_template',
                'format' => 'raw',
                'value' => function($model) {
                    if (empty($model->report_template)) {
                        return 'Шаблон не задан';
                    }
                    $html = '<ul>';
                    foreach ($model->report_template as $key => $value) {
                        $html .= '<li><strong>' . Html::encode($key) . ':</strong> ' . Html::encode($value) . '</li>';
                    }
                    $html .= '</ul>';
                    return $html;
                },
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

