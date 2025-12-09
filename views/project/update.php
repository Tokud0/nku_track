<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\Project;

/** @var yii\web\View $this */
/** @var app\models\Project $model */
/** @var yii\widgets\ActiveForm $form */

$this->title = 'Редактирование проекта';
$this->params['breadcrumbs'][] = ['label' => 'Проекты', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->title, 'url' => ['view', 'id' => (string)$model->_id]];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="project-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="project-form">

        <?php $form = ActiveForm::begin(); ?>

        <?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>

        <?= $form->field($model, 'description')->textarea(['rows' => 6]) ?>

        <?= $form->field($model, 'goals')->textarea(['rows' => 6]) ?>

        <?= $form->field($model, 'start_date')->input('date', [
            'value' => $model->start_date instanceof \MongoDB\BSON\UTCDateTime 
                ? date('Y-m-d', $model->start_date->toDateTime()->getTimestamp()) 
                : ''
        ]) ?>

        <?= $form->field($model, 'end_date')->input('date', [
            'value' => $model->end_date instanceof \MongoDB\BSON\UTCDateTime 
                ? date('Y-m-d', $model->end_date->toDateTime()->getTimestamp()) 
                : ''
        ]) ?>

        <?= $form->field($model, 'status')->dropDownList([
            Project::STATUS_DRAFT => 'Черновик',
            Project::STATUS_ACTIVE => 'Активный',
            Project::STATUS_REVIEW => 'На проверке',
            Project::STATUS_FINISHED => 'Завершен',
            Project::STATUS_FROZEN => 'Заморожен',
        ]) ?>

        <?php if (Yii::$app->user->identity->role === \app\models\User::ROLE_ADMIN || Yii::$app->user->identity->role === \app\models\User::ROLE_RECTOR): ?>
            <?php
            $departments = \app\models\Department::find()->all();
            $departmentList = [];
            foreach ($departments as $dept) {
                $departmentList[(string)$dept->_id] = $dept->name;
            }
            ?>
            <?= $form->field($model, 'department_id')->dropDownList(
                $departmentList,
                ['prompt' => 'Выберите подразделение']
            ) ?>
        <?php else: ?>
            <?php if ($model->department): ?>
                <div class="form-group">
                    <label class="control-label">Подразделение</label>
                    <div><?= Html::encode($model->department->name) ?></div>
                    <div class="help-block">Подразделение может изменить только администратор или ректор.</div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="form-group">
            <?= Html::submitButton('Сохранить', ['class' => 'btn btn-primary']) ?>
            <?= Html::a('Отмена', ['view', 'id' => (string)$model->_id], ['class' => 'btn btn-secondary']) ?>
        </div>

        <?php ActiveForm::end(); ?>

    </div>

</div>

