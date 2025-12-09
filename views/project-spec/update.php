<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\ProjectSpec;
use app\models\Project;

/** @var yii\web\View $this */
/** @var app\models\ProjectSpec $model */
/** @var app\models\Project $project */

$this->title = 'Редактирование технического задания';
$this->params['breadcrumbs'][] = ['label' => 'Проекты', 'url' => ['project/index']];
$this->params['breadcrumbs'][] = ['label' => $project->title, 'url' => ['project/view', 'id' => (string)$project->_id]];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="project-spec-update">

    <h1><?= Html::encode($this->title) ?></h1>
    <h3>Проект: <?= Html::encode($project->title) ?></h3>

    <div class="project-spec-form">

        <?php $form = ActiveForm::begin(); ?>

        <?= $form->field($model, 'tz_text')->textarea(['rows' => 10]) ?>

        <?= $form->field($model, 'report_period')->dropDownList([
            ProjectSpec::PERIOD_DAILY => 'Ежедневно',
            ProjectSpec::PERIOD_WEEKLY => 'Еженедельно',
            ProjectSpec::PERIOD_BIWEEKLY => 'Раз в две недели',
            ProjectSpec::PERIOD_MONTHLY => 'Ежемесячно',
            ProjectSpec::PERIOD_CUSTOM => 'Произвольный период',
        ], ['id' => 'report-period-select']) ?>

        <div id="custom-period-field" style="display: <?= $model->report_period === ProjectSpec::PERIOD_CUSTOM ? 'block' : 'none' ?>;">
            <?= $form->field($model, 'custom_period_days')->textInput(['type' => 'number', 'min' => 1]) ?>
        </div>

        <div class="form-group">
            <label>Этапы проекта (milestones)</label>
            <div id="milestones-container">
                <?php if (!empty($model->milestones)): ?>
                    <?php foreach ($model->milestones as $index => $milestone): ?>
                        <div class="milestone-item mb-2">
                            <div class="row">
                                <div class="col-md-5">
                                    <input type="text" name="milestones[<?= $index ?>][name]" class="form-control" value="<?= Html::encode($milestone['name'] ?? '') ?>" placeholder="Название этапа">
                                </div>
                                <div class="col-md-4">
                                    <input type="date" name="milestones[<?= $index ?>][deadline]" class="form-control" value="<?= Html::encode($milestone['deadline'] ?? '') ?>" placeholder="Дедлайн">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-check-label">
                                        <input type="checkbox" name="milestones[<?= $index ?>][done]" value="1" class="form-check-input" <?= isset($milestone['done']) && $milestone['done'] ? 'checked' : '' ?>> Выполнено
                                    </label>
                                </div>
                                <div class="col-md-1">
                                    <button type="button" class="btn btn-danger btn-sm remove-milestone">×</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="milestone-item mb-2">
                        <div class="row">
                            <div class="col-md-5">
                                <input type="text" name="milestones[0][name]" class="form-control" placeholder="Название этапа">
                            </div>
                            <div class="col-md-4">
                                <input type="date" name="milestones[0][deadline]" class="form-control" placeholder="Дедлайн">
                            </div>
                            <div class="col-md-2">
                                <label class="form-check-label">
                                    <input type="checkbox" name="milestones[0][done]" value="1" class="form-check-input"> Выполнено
                                </label>
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="btn btn-danger btn-sm remove-milestone">×</button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <button type="button" class="btn btn-secondary btn-sm mt-2" id="add-milestone">Добавить этап</button>
        </div>

        <div class="form-group">
            <label>Метрики успеха</label>
            <div id="metrics-container">
                <?php if (!empty($model->metrics)): ?>
                    <?php foreach ($model->metrics as $index => $metric): ?>
                        <div class="metric-item mb-2">
                            <div class="row">
                                <div class="col-md-11">
                                    <input type="text" name="metrics[<?= $index ?>]" class="form-control" value="<?= Html::encode($metric) ?>" placeholder="Метрика">
                                </div>
                                <div class="col-md-1">
                                    <button type="button" class="btn btn-danger btn-sm remove-metric">×</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="metric-item mb-2">
                        <div class="row">
                            <div class="col-md-11">
                                <input type="text" name="metrics[0]" class="form-control" placeholder="Метрика">
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="btn btn-danger btn-sm remove-metric">×</button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <button type="button" class="btn btn-secondary btn-sm mt-2" id="add-metric">Добавить метрику</button>
        </div>

        <div class="form-group">
            <label>Шаблон отчета (поля)</label>
            <div id="template-container">
                <?php if (!empty($model->report_template)): ?>
                    <?php $templateIndex = 0; ?>
                    <?php foreach ($model->report_template as $key => $value): ?>
                        <div class="template-item mb-2">
                            <div class="row">
                                <div class="col-md-5">
                                    <input type="text" name="report_template[<?= $templateIndex ?>][key]" class="form-control" value="<?= Html::encode($key) ?>" placeholder="Название поля">
                                </div>
                                <div class="col-md-6">
                                    <input type="text" name="report_template[<?= $templateIndex ?>][value]" class="form-control" value="<?= Html::encode($value) ?>" placeholder="Описание поля">
                                </div>
                                <div class="col-md-1">
                                    <button type="button" class="btn btn-danger btn-sm remove-template">×</button>
                                </div>
                            </div>
                        </div>
                        <?php $templateIndex++; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="template-item mb-2">
                        <div class="row">
                            <div class="col-md-5">
                                <input type="text" name="report_template[0][key]" class="form-control" placeholder="Название поля">
                            </div>
                            <div class="col-md-6">
                                <input type="text" name="report_template[0][value]" class="form-control" placeholder="Описание поля">
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="btn btn-danger btn-sm remove-template">×</button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <button type="button" class="btn btn-secondary btn-sm mt-2" id="add-template">Добавить поле</button>
        </div>

        <div class="form-group">
            <?= Html::submitButton('Сохранить', ['class' => 'btn btn-primary']) ?>
            <?= Html::a('Отмена', ['project/view', 'id' => (string)$project->_id], ['class' => 'btn btn-secondary']) ?>
        </div>

        <?php ActiveForm::end(); ?>

    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Показ/скрытие поля custom_period_days
    const periodSelect = document.getElementById('report-period-select');
    const customField = document.getElementById('custom-period-field');
    
    periodSelect.addEventListener('change', function() {
        if (this.value === 'custom') {
            customField.style.display = 'block';
        } else {
            customField.style.display = 'none';
        }
    });
    
    // Добавление этапов
    let milestoneIndex = <?= !empty($model->milestones) ? count($model->milestones) : 1 ?>;
    document.getElementById('add-milestone').addEventListener('click', function() {
        const container = document.getElementById('milestones-container');
        const newItem = document.createElement('div');
        newItem.className = 'milestone-item mb-2';
        newItem.innerHTML = `
            <div class="row">
                <div class="col-md-5">
                    <input type="text" name="milestones[${milestoneIndex}][name]" class="form-control" placeholder="Название этапа">
                </div>
                <div class="col-md-4">
                    <input type="date" name="milestones[${milestoneIndex}][deadline]" class="form-control" placeholder="Дедлайн">
                </div>
                <div class="col-md-2">
                    <label class="form-check-label">
                        <input type="checkbox" name="milestones[${milestoneIndex}][done]" value="1" class="form-check-input"> Выполнено
                    </label>
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-danger btn-sm remove-milestone">×</button>
                </div>
            </div>
        `;
        container.appendChild(newItem);
        milestoneIndex++;
    });
    
    // Удаление этапов
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-milestone')) {
            e.target.closest('.milestone-item').remove();
        }
    });
    
    // Добавление метрик
    let metricIndex = <?= !empty($model->metrics) ? count($model->metrics) : 1 ?>;
    document.getElementById('add-metric').addEventListener('click', function() {
        const container = document.getElementById('metrics-container');
        const newItem = document.createElement('div');
        newItem.className = 'metric-item mb-2';
        newItem.innerHTML = `
            <div class="row">
                <div class="col-md-11">
                    <input type="text" name="metrics[${metricIndex}]" class="form-control" placeholder="Метрика">
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-danger btn-sm remove-metric">×</button>
                </div>
            </div>
        `;
        container.appendChild(newItem);
        metricIndex++;
    });
    
    // Удаление метрик
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-metric')) {
            e.target.closest('.metric-item').remove();
        }
    });
    
    // Добавление полей шаблона
    let templateIndex = <?= !empty($model->report_template) ? count($model->report_template) : 1 ?>;
    document.getElementById('add-template').addEventListener('click', function() {
        const container = document.getElementById('template-container');
        const newItem = document.createElement('div');
        newItem.className = 'template-item mb-2';
        newItem.innerHTML = `
            <div class="row">
                <div class="col-md-5">
                    <input type="text" name="report_template[${templateIndex}][key]" class="form-control" placeholder="Название поля">
                </div>
                <div class="col-md-6">
                    <input type="text" name="report_template[${templateIndex}][value]" class="form-control" placeholder="Описание поля">
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-danger btn-sm remove-template">×</button>
                </div>
            </div>
        `;
        container.appendChild(newItem);
        templateIndex++;
    });
    
    // Удаление полей шаблона
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-template')) {
            e.target.closest('.template-item').remove();
        }
    });
});
</script>

