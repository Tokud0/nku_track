<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use app\models\Task;
use app\models\User;

/** @var yii\web\View $this */
/** @var app\models\Task $model */

$this->title = $model->title;
$this->params['breadcrumbs'][] = ['label' => 'Проекты', 'url' => ['project/index']];
$this->params['breadcrumbs'][] = ['label' => $model->project->title, 'url' => ['project/view', 'id' => (string)$model->project_id]];
$this->params['breadcrumbs'][] = $this->title;

$user = Yii::$app->user->identity;
$isExecutor = $user->role === User::ROLE_EXECUTOR && 
              $model->executor_id && 
              (string)$model->executor_id === (string)$user->_id;
?>
<div class="task-view">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= Html::encode($this->title) ?></h1>
        <div>
            <span class="badge badge-<?= $model->getStatusBadgeColor() ?> badge-lg mr-2">
                <?= $model->getStatusLabel() ?>
            </span>
            <span class="badge badge-<?= $model->getPriorityBadgeColor() ?> badge-lg">
                <?= $model->getPriorityLabel() ?>
            </span>
        </div>
    </div>

    <p>
        <?php if (($user->role === User::ROLE_MANAGER && (string)$model->project->manager_id === (string)$user->_id) || 
                  $user->role === User::ROLE_ADMIN): ?>
            <?= Html::a('Редактировать', ['update', 'id' => (string)$model->_id], ['class' => 'btn btn-primary']) ?>
            <?= Html::a('Удалить', ['delete', 'id' => (string)$model->_id], [
                'class' => 'btn btn-danger',
                'data' => [
                    'confirm' => 'Вы уверены, что хотите удалить эту задачу?',
                    'method' => 'post',
                ],
            ]) ?>
        <?php elseif ($isExecutor): ?>
            <?= Html::a('Редактировать', ['update', 'id' => (string)$model->_id], ['class' => 'btn btn-primary']) ?>
        <?php endif; ?>
        <?= Html::a('Назад к проекту', ['project/view', 'id' => (string)$model->project_id], ['class' => 'btn btn-secondary']) ?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            [
                'attribute' => 'description',
                'format' => 'raw',
                'value' => nl2br(Html::encode($model->description)),
            ],
            [
                'attribute' => 'project_id',
                'value' => Html::a($model->project->title, ['project/view', 'id' => (string)$model->project_id]),
                'format' => 'raw',
            ],
            [
                'attribute' => 'executor_id',
                'value' => $model->executor ? $model->executor->fio : 'Не назначен',
            ],
            [
                'attribute' => 'creator_id',
                'value' => $model->creator ? $model->creator->fio : '-',
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
                'attribute' => 'due_date',
                'value' => function($model) {
                    if ($model->due_date instanceof \MongoDB\BSON\UTCDateTime) {
                        $dueDate = $model->due_date->toDateTime()->getTimestamp();
                        $now = time();
                        $daysLeft = floor(($dueDate - $now) / (24 * 60 * 60));
                        
                        $class = $daysLeft < 0 ? 'text-danger' : ($daysLeft <= 3 ? 'text-warning' : 'text-success');
                        return '<span class="' . $class . '">' . date('d.m.Y', $dueDate) . ' (' . $daysLeft . ' дн.)</span>';
                    }
                    return '-';
                },
                'format' => 'raw',
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

    <!-- Комментарии к задаче -->
    <div class="mt-4">
        <h3>Комментарии</h3>
        <?php
        $comments = \app\models\Comment::find()
            ->where(['task_id' => $model->_id])
            ->orderBy(['created_at' => SORT_ASC])
            ->all();
        ?>
        
        <?php if (empty($comments)): ?>
            <p class="text-muted">Комментариев пока нет.</p>
        <?php else: ?>
            <div class="list-group">
                <?php foreach ($comments as $comment): ?>
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between">
                            <div>
                                <strong><?= Html::encode($comment->author->fio) ?></strong>
                                <small class="text-muted">
                                    <?php if ($comment->created_at instanceof \MongoDB\BSON\UTCDateTime): ?>
                                        <?= date('d.m.Y H:i', $comment->created_at->toDateTime()->getTimestamp()) ?>
                                    <?php endif; ?>
                                </small>
                            </div>
                        </div>
                        <p class="mb-0 mt-2"><?= nl2br(Html::encode($comment->text)) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Форма добавления комментария -->
        <?php if ($user->role !== User::ROLE_RECTOR): ?>
            <div class="mt-3">
                <?php $commentForm = \yii\widgets\ActiveForm::begin([
                    'action' => ['comment/create'],
                    'method' => 'post',
                ]); ?>
                
                <?= Html::hiddenInput('Comment[task_id]', (string)$model->_id) ?>
                
                <div class="form-group">
                    <label>Добавить комментарий</label>
                    <textarea name="Comment[text]" class="form-control" rows="3" required></textarea>
                </div>
                
                <?= Html::submitButton('Добавить комментарий', ['class' => 'btn btn-primary']) ?>
                
                <?php \yii\widgets\ActiveForm::end(); ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<style>
.task-view .badge-lg {
    font-size: 1rem;
    padding: 0.5rem 1rem;
}
</style>

