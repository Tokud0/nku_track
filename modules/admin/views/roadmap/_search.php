<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\Department;

/** @var yii\web\View $this */
/** @var app\modules\admin\models\RoadmapSearch $model */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="roadmap-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
        'options' => [
            'data-pjax' => 1
        ],
    ]); ?>

    <div class="row">
        <div class="col-md-4">
            <?php
            $departments = Department::find()
                ->where(['parent_id' => null])
                ->orderBy(['name' => SORT_ASC])
                ->all();
            $departmentList = ['' => 'Все подразделения'];
            foreach ($departments as $dept) {
                $departmentList[(string)$dept->_id] = $dept->name;
            }
            ?>
            <?= $form->field($model, 'department_id', ['labelOptions' => ['style' => 'display:none']])->dropDownList($departmentList, ['class' => 'form-control']) ?>
        </div>
        <div class="col-md-4">
            <?= $form->field($model, 'department_name', ['labelOptions' => ['style' => 'display:none']])->textInput(['placeholder' => 'Введите название подразделения']) ?>
        </div>
        <div class="col-md-4" style="padding-top: 32px;">
            <div class="form-group">
                <?= Html::submitButton('Поиск', ['class' => 'btn btn-primary']) ?>
                <?= Html::a('Сбросить', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
            </div>
        </div>
    </div>

    <?php ActiveForm::end(); ?>

</div>

