<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\modules\admin\models\UserSearch $model */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="user-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
        'options' => [
            'data-pjax' => 1
        ],
    ]); ?>

    <?= $form->field($model, 'fio') ?>

    <?= $form->field($model, 'email') ?>

    <?= $form->field($model, 'role')->dropDownList([
        '' => 'Все',
        \app\models\User::ROLE_ADMIN => 'Администратор',
        \app\models\User::ROLE_RECTOR => 'Руководитель',
        \app\models\User::ROLE_TOP_MANAGER => 'Топ-менеджер',
        \app\models\User::ROLE_MANAGER => 'Менеджер',
        \app\models\User::ROLE_EXECUTOR => 'Исполнитель',
    ]) ?>

    <?= $form->field($model, 'department') ?>

    <div class="form-group">
        <?= Html::submitButton('Поиск', ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Сбросить', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>

