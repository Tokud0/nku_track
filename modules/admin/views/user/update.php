<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\User;

/** @var yii\web\View $this */
/** @var app\models\User $model */
/** @var yii\widgets\ActiveForm $form */

$this->title = 'Редактирование пользователя';
$this->params['breadcrumbs'][] = ['label' => 'Админка', 'url' => ['/admin']];
$this->params['breadcrumbs'][] = ['label' => 'Пользователи', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->fio, 'url' => ['view', 'id' => (string)$model->_id]];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="user-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="user-form">

        <?php $form = ActiveForm::begin(); ?>

        <?= $form->field($model, 'fio')->textInput(['maxlength' => true]) ?>

        <?= $form->field($model, 'email')->textInput(['maxlength' => true, 'type' => 'email']) ?>

        <?= $form->field($model, 'password')->passwordInput(['maxlength' => true])->hint('Оставьте пустым, если не хотите менять пароль') ?>

        <?= $form->field($model, 'role')->dropDownList([
            User::ROLE_ADMIN => 'Администратор',
            User::ROLE_RECTOR => 'Ректор',
            User::ROLE_MANAGER => 'Руководитель',
            User::ROLE_EXECUTOR => 'Исполнитель',
        ], ['prompt' => 'Выберите роль']) ?>

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

        <div class="form-group">
            <?= Html::submitButton('Сохранить', ['class' => 'btn btn-primary']) ?>
            <?= Html::a('Отмена', ['view', 'id' => (string)$model->_id], ['class' => 'btn btn-secondary']) ?>
        </div>

        <?php ActiveForm::end(); ?>

    </div>

</div>

