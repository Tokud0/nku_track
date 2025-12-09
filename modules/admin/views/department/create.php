<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\Department;

/** @var yii\web\View $this */
/** @var app\models\Department $model */
/** @var yii\widgets\ActiveForm $form */

$this->title = 'Создание подразделения';
$this->params['breadcrumbs'][] = ['label' => 'Админка', 'url' => ['/admin']];
$this->params['breadcrumbs'][] = ['label' => 'Подразделения', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="department-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="department-form">

        <?php $form = ActiveForm::begin(); ?>

        <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

        <?= $form->field($model, 'description')->textarea(['rows' => 6]) ?>

        <div class="form-group">
            <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
            <?= Html::a('Отмена', ['index'], ['class' => 'btn btn-secondary']) ?>
        </div>

        <?php ActiveForm::end(); ?>

    </div>

</div>

