<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\Project $model */

$this->title = 'Редактировать глобальный проект';
$this->params['breadcrumbs'][] = ['label' => 'Глобальный проект', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->title, 'url' => ['view', 'id' => (string)$model->_id]];
$this->params['breadcrumbs'][] = 'Редактировать';
?>

<div class="project-update">
    <div class="nku-card">
        <div class="nku-card__header">
            <h5 class="mb-0">Редактировать глобальный проект</h5>
        </div>
        <div class="nku-card__body">
            <?= $this->render('_form', [
                'model' => $model,
            ]) ?>
        </div>
    </div>
</div>

