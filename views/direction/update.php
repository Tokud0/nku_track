<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\Direction $model */

$this->title = 'Редактирование: ' . $model->title;
$this->params['breadcrumbs'][] = ['label' => 'Глобальный проект', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->title, 'url' => ['view', 'id' => (string)$model->_id]];
$this->params['breadcrumbs'][] = 'Редактирование';
?>

<div class="direction-update">
    <div class="nku-card">
        <div class="nku-card__header">
            <h2 class="mb-0"><?= Html::encode($this->title) ?></h2>
        </div>
        <div class="nku-card__body">
            <?= $this->render('_form', [
                'model' => $model,
            ]) ?>
        </div>
    </div>
</div>
