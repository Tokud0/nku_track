<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\User $model */
/** @var yii\widgets\ActiveForm $form */

$this->title = 'Редактирование профиля';
$this->params['breadcrumbs'][] = ['label' => 'Мой профиль', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="profile-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="nku-card mt-4">
        <div class="nku-card__body">
            <?php $form = ActiveForm::begin(); ?>

            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model, 'fio')->textInput(['maxlength' => true, 'class' => 'form-control']) ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($model, 'email')->textInput(['maxlength' => true, 'type' => 'email', 'class' => 'form-control']) ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model, 'password')->passwordInput(['maxlength' => true, 'class' => 'form-control'])->hint('Оставьте пустым, если не хотите менять пароль') ?>
                </div>
            </div>

            <div class="form-group mt-4">
                <?= Html::submitButton('Сохранить', ['class' => 'nku-btn nku-btn--primary']) ?>
                <?= Html::a('Отмена', ['index'], ['class' => 'nku-btn nku-btn--secondary']) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>

</div>

