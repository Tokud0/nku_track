<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\Direction $model */

$this->title = 'Создание направления';
$this->params['breadcrumbs'][] = ['label' => 'Глобальный проект', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="direction-create">
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
