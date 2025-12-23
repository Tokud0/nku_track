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
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8">
            <div class="nku-card" style="margin-top: 3rem;">
                <div class="nku-card__header">
                    <h3 class="mb-0">
                        <i class="fas fa-user-plus"></i>
                        <?= Html::encode($this->title) ?>
                    </h3>
                </div>
                <div class="nku-card__body">
                    <p class="text-muted mb-4">Пожалуйста, заполните следующие поля для регистрации:</p>

                    <?php $form = ActiveForm::begin([
                        'id' => 'signup-form',
                        'fieldConfig' => [
                            'template' => "{label}\n{input}\n{error}",
                            'labelOptions' => ['class' => 'form-label fw-semibold'],
                            'inputOptions' => ['class' => 'form-control'],
                            'errorOptions' => ['class' => 'invalid-feedback'],
                        ],
                    ]); ?>

                    <?= $form->field($model, 'fio')->textInput([
                        'autofocus' => true,
                        'placeholder' => 'Введите ФИО'
                    ]) ?>

                    <?= $form->field($model, 'email')->textInput([
                        'type' => 'email',
                        'placeholder' => 'Введите email'
                    ]) ?>

                    <?= $form->field($model, 'password')->passwordInput([
                        'placeholder' => 'Введите пароль (минимум 6 символов)'
                    ]) ?>

                    <?= $form->field($model, 'password_repeat')->passwordInput([
                        'placeholder' => 'Повторите пароль'
                    ]) ?>

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

                    <div class="d-grid gap-2 mt-4">
                        <?= Html::submitButton(
                            '<i class="fas fa-user-plus me-2"></i>Зарегистрироваться', 
                            ['class' => 'nku-btn nku-btn--primary nku-btn--lg', 'name' => 'signup-button']
                        ) ?>
                    </div>

                    <?php ActiveForm::end(); ?>
                </div>
                <div class="nku-card__footer text-center">
                    <p class="mb-0 text-muted">
                        Уже есть аккаунт? 
                        <?= Html::a('Войти', ['site/login'], ['class' => 'text-decoration-none fw-semibold']) ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

