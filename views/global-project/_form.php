<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\Project;
use app\models\User;

/** @var yii\web\View $this */
/** @var app\models\Project $model */
/** @var yii\widgets\ActiveForm $form */
?>

<?php $form = ActiveForm::begin(); ?>

<div class="row">
    <div class="col-md-8">
        <?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>

        <?= $form->field($model, 'description')->textarea(['rows' => 6]) ?>

        <?= $form->field($model, 'goals')->textarea(['rows' => 4]) ?>
    </div>

    <div class="col-md-4">
        <?= $form->field($model, 'status')->dropDownList([
            Project::STATUS_DRAFT => 'Черновик',
            Project::STATUS_ACTIVE => 'Активный',
            Project::STATUS_REVIEW => 'На проверке',
            Project::STATUS_FINISHED => 'Завершен',
            Project::STATUS_FROZEN => 'Заморожен',
        ]) ?>

        <?php
        // Получаем всех пользователей для выбора менеджера
        $users = User::find()->all();
        $userList = [];
        foreach ($users as $user) {
            $userList[(string)$user->_id] = $user->fio . ' (' . $user->email . ')';
        }
        ?>
        <?= $form->field($model, 'manager_id')->dropDownList($userList, ['prompt' => 'Выберите руководителя']) ?>

        <?= $form->field($model, 'start_date_str')->textInput(['type' => 'date']) ?>

        <?= $form->field($model, 'end_date_str')->textInput(['type' => 'date']) ?>
    </div>
</div>

<div class="form-group">
    <?= Html::submitButton('Сохранить', ['class' => 'nku-btn nku-btn--primary']) ?>
    <?= Html::a('Отмена', ['view', 'id' => (string)$model->_id], ['class' => 'nku-btn nku-btn--secondary']) ?>
</div>

<?php ActiveForm::end(); ?>

