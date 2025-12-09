<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\Task;
use app\models\Project;

/** @var yii\web\View $this */
/** @var app\models\Task $model */
/** @var app\models\Project $project */
/** @var app\models\User[] $executors */

$this->title = 'Создание задачи';
$this->params['breadcrumbs'][] = ['label' => 'Проекты', 'url' => ['project/index']];
$this->params['breadcrumbs'][] = ['label' => $project->title, 'url' => ['project/view', 'id' => (string)$project->_id]];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="task-create">

    <h1><?= Html::encode($this->title) ?></h1>
    <h3>Проект: <?= Html::encode($project->title) ?></h3>

    <div class="task-form">

        <?php if (Yii::$app->session->hasFlash('error')): ?>
            <div class="alert alert-danger">
                <?= Yii::$app->session->getFlash('error') ?>
            </div>
        <?php endif; ?>

        <?php $form = ActiveForm::begin(); ?>

        <?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>

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
                <?= $form->field($model, 'priority')->dropDownList([
                    Task::PRIORITY_LOW => 'Низкий',
                    Task::PRIORITY_MEDIUM => 'Средний',
                    Task::PRIORITY_HIGH => 'Высокий',
                    Task::PRIORITY_CRITICAL => 'Критический',
                ]) ?>
            </div>
        </div>

        <?php if (!empty($executors)): ?>
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
        <?php else: ?>
            <div class="alert alert-warning">
                В проекте нет назначенных исполнителей. Сначала назначьте исполнителей в проекте.
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6">
                <?= $form->field($model, 'start_date')->input('date') ?>
            </div>
            <div class="col-md-6">
                <?= $form->field($model, 'due_date')->input('date') ?>
            </div>
        </div>

        <?= $form->field($model, 'progress')->textInput(['type' => 'number', 'min' => 0, 'max' => 100]) ?>

        <div class="form-group">
            <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
            <?= Html::a('Отмена', ['project/view', 'id' => (string)$project->_id], ['class' => 'btn btn-secondary']) ?>
        </div>

        <?php ActiveForm::end(); ?>

    </div>

</div>

