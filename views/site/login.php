<?php

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var app\models\LoginForm $model */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = 'Вход';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="site-login">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-7">
            <div class="nku-card" style="margin-top: 3rem;">
                <div class="nku-card__header">
                    <h3 class="mb-0">
                        <i class="fas fa-sign-in-alt"></i>
                        <?= Html::encode($this->title) ?>
                    </h3>
                </div>
                <div class="nku-card__body">
                    <p class="text-muted mb-4">Пожалуйста, заполните следующие поля для входа:</p>

                    <?php $form = ActiveForm::begin([
                        'id' => 'login-form',
                        'fieldConfig' => [
                            'template' => "{label}\n{input}\n{error}",
                            'labelOptions' => ['class' => 'form-label fw-semibold'],
                            'inputOptions' => ['class' => 'form-control'],
                            'errorOptions' => ['class' => 'invalid-feedback'],
                        ],
                    ]); ?>

                    <?= $form->field($model, 'email')->textInput([
                        'autofocus' => true, 
                        'type' => 'email',
                        'placeholder' => 'Введите email'
                    ]) ?>

                    <?= $form->field($model, 'password')->passwordInput([
                        'placeholder' => 'Введите пароль'
                    ]) ?>

                    <?= $form->field($model, 'rememberMe')->checkbox([
                        'template' => "<div class=\"form-check\">{input} {label}</div>\n{error}",
                        'labelOptions' => ['class' => 'form-check-label'],
                        'inputOptions' => ['class' => 'form-check-input'],
                    ]) ?>

                    <div class="d-grid gap-2 mt-4">
                        <?= Html::submitButton(
                            '<i class="fas fa-sign-in-alt me-2"></i>Войти', 
                            ['class' => 'nku-btn nku-btn--primary nku-btn--lg', 'name' => 'login-button']
                        ) ?>
                    </div>

                    <?php ActiveForm::end(); ?>
                </div>
                <div class="nku-card__footer text-center">
                    <p class="mb-0 text-muted">
                        Нет аккаунта? 
                        <?= Html::a('Зарегистрироваться', ['site/signup'], ['class' => 'text-decoration-none fw-semibold']) ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
