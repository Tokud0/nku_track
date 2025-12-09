<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\Project;

/** @var yii\web\View $this */
/** @var app\models\Project $model */
/** @var app\models\User[] $executors */

$this->title = 'Назначение исполнителей';
$this->params['breadcrumbs'][] = ['label' => 'Проекты', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->title, 'url' => ['view', 'id' => (string)$model->_id]];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="project-assign-executors">

    <h1><?= Html::encode($this->title) ?></h1>
    <h3>Проект: <?= Html::encode($model->title) ?></h3>

    <?php $form = ActiveForm::begin(); ?>

    <div class="form-group">
        <label>Выберите исполнителей:</label>
        <div class="row">
            <?php foreach ($executors as $executor): ?>
                <div class="col-md-4 mb-2">
                    <div class="form-check">
                        <?php
                        $checked = false;
                        foreach ($model->executors ?: [] as $executorId) {
                            if ((string)$executorId === (string)$executor->_id) {
                                $checked = true;
                                break;
                            }
                        }
                        ?>
                        <input 
                            class="form-check-input" 
                            type="checkbox" 
                            name="executors[]" 
                            value="<?= (string)$executor->_id ?>" 
                            id="executor_<?= (string)$executor->_id ?>"
                            <?= $checked ? 'checked' : '' ?>
                        >
                        <label class="form-check-label" for="executor_<?= (string)$executor->_id ?>">
                            <?= Html::encode($executor->fio) ?> 
                            <?php if ($executor->department): ?>
                                <small class="text-muted">(<?= Html::encode($executor->department->name) ?>)</small>
                            <?php endif; ?>
                        </label>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="form-group">
        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
        <?= Html::a('Отмена', ['view', 'id' => (string)$model->_id], ['class' => 'btn btn-secondary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>

