<?php
/** @var yii\web\View $this */

use yii\bootstrap5\Breadcrumbs;

if (empty($this->params['breadcrumbs'])) {
    return;
}
?>

<?= Breadcrumbs::widget([
    'links' => $this->params['breadcrumbs'],
    'options' => ['class' => 'breadcrumb'],
]) ?>


