<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\Task;
use app\models\Project;

/** @var yii\web\View $this */
/** @var app\models\Task $model */
/** @var app\models\Project $project */
/** @var app\models\User[] $executors */

$this->title = 'Редактирование задачи';
$this->params['breadcrumbs'][] = ['label' => 'Проекты', 'url' => ['project/index']];
$this->params['breadcrumbs'][] = ['label' => $project->title, 'url' => ['project/view', 'id' => (string)$project->_id]];
$this->params['breadcrumbs'][] = $this->title;

$user = Yii::$app->user->identity;
$isExecutor = $user->role === \app\models\User::ROLE_EXECUTOR && 
              $model->executor_id && 
              (string)$model->executor_id === (string)$user->_id;
?>

<div class="task-update">

    <h1><?= Html::encode($this->title) ?></h1>
    <h3>Проект: <?= Html::encode($project->title) ?></h3>

    <div class="task-form">

        <?php $form = ActiveForm::begin(); ?>

        <?php if (!$isExecutor): ?>
            <?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>
        <?php else: ?>
            <div class="form-group">
                <label class="control-label">Название</label>
                <div><?= Html::encode($model->title) ?></div>
            </div>
        <?php endif; ?>

        <?= $form->field($model, 'description')->textarea(['rows' => 6]) ?>

        <div class="row">
            <div class="col-md-6">
                <?= $form->field($model, 'status')->dropDownList([
                    Task::STATUS_TODO => 'К выполнению',
                    Task::STATUS_IN_PROGRESS => 'В работе',
                    Task::STATUS_REVIEW => 'На проверке',
                    Task::STATUS_DONE => 'Выполнено',
                    Task::STATUS_CANCELED => 'Отменено',
                ]) ?>
            </div>
            <div class="col-md-6">
                <?php if (!$isExecutor): ?>
                    <?= $form->field($model, 'priority')->dropDownList([
                        Task::PRIORITY_LOW => 'Низкий',
                        Task::PRIORITY_MEDIUM => 'Средний',
                        Task::PRIORITY_HIGH => 'Высокий',
                        Task::PRIORITY_CRITICAL => 'Критический',
                    ]) ?>
                <?php else: ?>
                    <div class="form-group">
                        <label class="control-label">Приоритет</label>
                        <div>
                            <span class="badge badge-<?= $model->getPriorityBadgeColor() ?>">
                                <?= $model->getPriorityLabel() ?>
                            </span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!$isExecutor && !empty($executors)): ?>
            <?php
            $executorList = [];
            foreach ($executors as $executor) {
                $executorList[(string)$executor->_id] = $executor->fio;
            }
            ?>
            <?= $form->field($model, 'executor_id')->dropDownList(
                $executorList,
                ['prompt' => 'Выберите исполнителя']
            ) ?>
        <?php elseif ($isExecutor && $model->executor): ?>
            <div class="form-group">
                <label class="control-label">Исполнитель</label>
                <div><?= Html::encode($model->executor->fio) ?></div>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6">
                <?= $form->field($model, 'start_date')->input('date', [
                    'value' => $model->start_date instanceof \MongoDB\BSON\UTCDateTime 
                        ? date('Y-m-d', $model->start_date->toDateTime()->getTimestamp()) 
                        : ''
                ]) ?>
            </div>
            <div class="col-md-6">
                <?= $form->field($model, 'due_date')->input('date', [
                    'value' => $model->due_date instanceof \MongoDB\BSON\UTCDateTime 
                        ? date('Y-m-d', $model->due_date->toDateTime()->getTimestamp()) 
                        : ''
                ]) ?>
            </div>
        </div>

        <?= $form->field($model, 'progress')->textInput(['type' => 'number', 'min' => 0, 'max' => 100]) ?>

        <div class="form-group">
            <?= Html::submitButton('Сохранить', ['class' => 'btn btn-primary']) ?>
            <?php if (!$isExecutor): ?>
                <?= Html::a('Удалить', ['task/delete', 'id' => (string)$model->_id], [
                    'class' => 'btn btn-danger',
                    'data' => [
                        'confirm' => 'Вы уверены, что хотите удалить эту задачу?',
                        'method' => 'post',
                    ],
                ]) ?>
            <?php endif; ?>
            <?= Html::a('Отмена', ['project/view', 'id' => (string)$project->_id], ['class' => 'btn btn-secondary']) ?>
        </div>

        <?php ActiveForm::end(); ?>

    </div>

</div>

