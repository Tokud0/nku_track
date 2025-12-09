<?php

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */

/** @var app\models\SignupForm $model */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = 'Регистрация';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="site-signup">
    <h1><?= Html::encode($this->title) ?></h1>

    <p>Пожалуйста, заполните следующие поля для регистрации:</p>

    <div class="row">
        <div class="col-lg-5">

            <?php $form = ActiveForm::begin([
                'id' => 'signup-form',
                'fieldConfig' => [
                    'template' => "{label}\n{input}\n{error}",
                    'labelOptions' => ['class' => 'col-lg-1 col-form-label mr-lg-3'],
                    'inputOptions' => ['class' => 'col-lg-3 form-control'],
                    'errorOptions' => ['class' => 'col-lg-7 invalid-feedback'],
                ],
            ]); ?>

            <?= $form->field($model, 'fio')->textInput(['autofocus' => true]) ?>

            <?= $form->field($model, 'email')->textInput(['type' => 'email']) ?>

            <?= $form->field($model, 'password')->passwordInput() ?>

            <?= $form->field($model, 'password_repeat')->passwordInput() ?>

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
                <div>
                    <?= Html::submitButton('Зарегистрироваться', ['class' => 'btn btn-primary', 'name' => 'signup-button']) ?>
                </div>
            </div>

            <?php ActiveForm::end(); ?>

            <div style="color:#999; margin-top: 20px;">
                Уже есть аккаунт? <?= Html::a('Войти', ['site/login'], ['class' => 'btn btn-link']) ?>
            </div>

        </div>
    </div>
</div>

