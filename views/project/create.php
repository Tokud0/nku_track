<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\Project;

/** @var yii\web\View $this */
/** @var app\models\Project $model */
/** @var yii\widgets\ActiveForm $form */

$this->title = 'Создание проекта';
$this->params['breadcrumbs'][] = ['label' => 'Проекты', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="project-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="project-form">

        <?php $form = ActiveForm::begin(); ?>

        <?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>

        <?= $form->field($model, 'description')->textarea(['rows' => 6]) ?>

        <?= $form->field($model, 'goals')->textarea(['rows' => 6]) ?>

        <?= $form->field($model, 'start_date')->input('date') ?>

        <?= $form->field($model, 'end_date')->input('date') ?>

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
        <?php endif; ?>

        <div class="form-group">
            <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
            <?= Html::a('Отмена', ['index'], ['class' => 'btn btn-secondary']) ?>
        </div>

        <?php ActiveForm::end(); ?>

    </div>

</div>

