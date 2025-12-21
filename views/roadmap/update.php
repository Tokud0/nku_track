<?php

use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var app\models\Roadmap $roadmap */
/** @var app\models\RoadmapStage[] $stages */

$this->title = 'Редактирование дорожной карты';
$this->params['breadcrumbs'][] = ['label' => 'Дорожная карта', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

// Регистрируем JavaScript для работы конструктора
$stagesJson = Json::encode(array_map(function($stage) {
    $goals = [];
    foreach ($stage->goals as $goal) {
        $goals[] = [
            'id' => (string)$goal->_id,
            'title' => $goal->title,
            'description' => $goal->description,
        ];
    }
    return [
        'id' => (string)$stage->_id,
        'name' => $stage->name,
        'start_month' => $stage->start_month,
        'end_month' => $stage->end_month,
        'description' => $stage->description,
        'goals' => $goals,
    ];
}, $stages));

$saveStageUrl = Url::to(['save-stage']);
$saveGoalUrl = Url::to(['save-goal']);
$deleteStageUrl = Url::to(['delete-stage']);
$deleteGoalUrl = Url::to(['delete-goal']);
$roadmapId = (string)$roadmap->_id;
$csrfToken = Yii::$app->request->csrfToken;
$csrfParam = Yii::$app->request->csrfParam;

$js = <<<JS
var stages = $stagesJson;
var roadmapId = '$roadmapId';
var saveStageUrl = '$saveStageUrl';
var saveGoalUrl = '$saveGoalUrl';
var deleteStageUrl = '$deleteStageUrl';
var deleteGoalUrl = '$deleteGoalUrl';
var csrfToken = '$csrfToken';
var csrfParam = '$csrfParam';

function renderStages() {
    var container = $('#stages-container');
    container.empty();
    
    if (stages.length === 0) {
        container.html('<div class="alert alert-info">Этапы не добавлены. Нажмите кнопку "+" для добавления этапа.</div>');
        return;
    }
    
    stages.forEach(function(stage, stageIndex) {
        var stageHtml = '<div class="card mb-4 stage-item" data-stage-id="' + stage.id + '">';
        stageHtml += '<div class="card-header bg-primary text-white">';
        stageHtml += '<div class="d-flex justify-content-between align-items-center">';
        stageHtml += '<h5 class="mb-0">Этап ' + (stageIndex + 1) + ': <span class="stage-name-display">' + escapeHtml(stage.name) + '</span></h5>';
        stageHtml += '<button type="button" class="btn btn-sm btn-danger delete-stage-btn" data-stage-id="' + stage.id + '">Удалить этап</button>';
        stageHtml += '</div></div>';
        stageHtml += '<div class="card-body">';
        
        // Форма редактирования этапа
        stageHtml += '<div class="stage-form">';
        stageHtml += '<div class="row mb-3">';
        stageHtml += '<div class="col-md-6">';
        stageHtml += '<label>Название этапа</label>';
        stageHtml += '<input type="text" class="form-control stage-name" value="' + escapeHtml(stage.name) + '" placeholder="Например: Создание офиса">';
        stageHtml += '</div>';
        stageHtml += '<div class="col-md-3">';
        stageHtml += '<label>Начало (месяц)</label>';
        stageHtml += '<input type="number" class="form-control stage-start-month" value="' + stage.start_month + '" min="0">';
        stageHtml += '</div>';
        stageHtml += '<div class="col-md-3">';
        stageHtml += '<label>Конец (месяц)</label>';
        stageHtml += '<input type="number" class="form-control stage-end-month" value="' + stage.end_month + '" min="0">';
        stageHtml += '</div>';
        stageHtml += '</div>';
        stageHtml += '<div class="mb-3">';
        stageHtml += '<label>Описание этапа</label>';
        stageHtml += '<textarea class="form-control stage-description" rows="3">' + escapeHtml(stage.description || '') + '</textarea>';
        stageHtml += '</div>';
        stageHtml += '<button type="button" class="btn btn-primary save-stage-btn" data-stage-id="' + stage.id + '">Сохранить этап</button>';
        stageHtml += '</div>';
        
        // Цели этапа
        stageHtml += '<hr class="my-4">';
        stageHtml += '<h6>Цели этапа <button type="button" class="btn btn-sm btn-success add-goal-btn" data-stage-id="' + stage.id + '">+ Добавить цель</button></h6>';
        stageHtml += '<div class="goals-container" data-stage-id="' + stage.id + '">';
        
        if (stage.goals && stage.goals.length > 0) {
            stage.goals.forEach(function(goal, goalIndex) {
                stageHtml += renderGoal(goal, goalIndex);
            });
        } else {
            stageHtml += '<p class="text-muted">Цели не добавлены</p>';
        }
        
        stageHtml += '</div>';
        stageHtml += '</div></div>';
        
        container.append(stageHtml);
    });
    
    attachEventHandlers();
}

function renderGoal(goal, index) {
    var goalHtml = '<div class="card mb-3 goal-item" data-goal-id="' + goal.id + '" data-stage-id="' + (goal.stage_id || '') + '">';
    goalHtml += '<div class="card-body">';
    goalHtml += '<div class="d-flex justify-content-between align-items-start mb-2">';
    goalHtml += '<h6 class="mb-0">Цель ' + (index + 1) + '</h6>';
    goalHtml += '<button type="button" class="btn btn-sm btn-danger delete-goal-btn" data-goal-id="' + goal.id + '">Удалить</button>';
    goalHtml += '</div>';
    goalHtml += '<div class="mb-2">';
    goalHtml += '<label>Название цели</label>';
    goalHtml += '<input type="text" class="form-control goal-title" value="' + escapeHtml(goal.title) + '">';
    goalHtml += '</div>';
    goalHtml += '<div class="mb-2">';
    goalHtml += '<label>Описание цели</label>';
    goalHtml += '<textarea class="form-control goal-description" rows="2">' + escapeHtml(goal.description || '') + '</textarea>';
    goalHtml += '</div>';
    goalHtml += '<button type="button" class="btn btn-primary btn-sm save-goal-btn" data-goal-id="' + goal.id + '">Сохранить цель</button>';
    goalHtml += '</div></div>';
    return goalHtml;
}

function escapeHtml(text) {
    if (!text) return '';
    var map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

function attachEventHandlers() {
    
    // Сохранение этапа
    $('.save-stage-btn').off('click').on('click', function() {
        var stageId = $(this).data('stage-id');
        var stageItem = $(this).closest('.stage-item');
        var stageIndex = stages.findIndex(function(s) {
            return (s.id && s.id === stageId) || (!s.id && !stageId);
        });
        
        if (stageIndex === -1) return;
        
        var stage = stages[stageIndex];
        stage.name = stageItem.find('.stage-name').val();
        stage.start_month = parseInt(stageItem.find('.stage-start-month').val()) || 0;
        stage.end_month = parseInt(stageItem.find('.stage-end-month').val()) || 0;
        stage.description = stageItem.find('.stage-description').val();
        
        $.ajax({
            url: saveStageUrl,
            method: 'POST',
            data: {
                id: stage.id || '',
                roadmap_id: roadmapId,
                name: stage.name,
                start_month: stage.start_month,
                end_month: stage.end_month,
                description: stage.description,
                [csrfParam]: csrfToken
            },
            success: function(response) {
                if (response.success) {
                    if (!stage.id) {
                        stage.id = response.stage.id;
                    }
                    stageItem.find('.stage-name-display').text(stage.name);
                    alert('Этап успешно сохранен!');
                } else {
                    alert('Ошибка: ' + response.message);
                }
            },
            error: function() {
                alert('Ошибка при сохранении этапа');
            }
        });
    });
    
    // Удаление этапа
    $('.delete-stage-btn').off('click').on('click', function() {
        if (!confirm('Вы уверены, что хотите удалить этот этап? Все цели этапа также будут удалены.')) {
            return;
        }
        
        var stageId = $(this).data('stage-id');
        if (!stageId) {
            // Новый этап, просто удаляем из массива
            var stageIndex = stages.findIndex(function(s) { return !s.id; });
            if (stageIndex !== -1) {
                stages.splice(stageIndex, 1);
                renderStages();
            }
            return;
        }
        
        $.ajax({
            url: deleteStageUrl,
            method: 'POST',
            data: {
                id: stageId,
                [csrfParam]: csrfToken
            },
            success: function(response) {
                window.location.reload();
            },
            error: function() {
                alert('Ошибка при удалении этапа');
            }
        });
    });
    
    // Добавление новой цели
    $('.add-goal-btn').off('click').on('click', function() {
        var stageId = $(this).data('stage-id');
        var stageIndex = stages.findIndex(function(s) { return s.id === stageId; });
        if (stageIndex === -1) return;
        
        if (!stages[stageIndex].goals) {
            stages[stageIndex].goals = [];
        }
        
        var newGoal = {
            id: null,
            title: '',
            description: '',
            stage_id: stageId
        };
        stages[stageIndex].goals.push(newGoal);
        renderStages();
    });
    
    // Сохранение цели
    $('.save-goal-btn').off('click').on('click', function() {
        var goalId = $(this).data('goal-id');
        var goalItem = $(this).closest('.goal-item');
        var stageId = goalItem.data('stage-id');
        
        var stageIndex = stages.findIndex(function(s) { return s.id === stageId; });
        if (stageIndex === -1) return;
        
        var goalIndex = stages[stageIndex].goals.findIndex(function(g) {
            return (g.id && g.id === goalId) || (!g.id && !goalId);
        });
        if (goalIndex === -1) return;
        
        var goal = stages[stageIndex].goals[goalIndex];
        goal.title = goalItem.find('.goal-title').val();
        goal.description = goalItem.find('.goal-description').val();
        goal.stage_id = stageId;
        
        $.ajax({
            url: saveGoalUrl,
            method: 'POST',
            data: {
                id: goal.id || '',
                stage_id: stageId,
                title: goal.title,
                description: goal.description,
                _csrf: yii.getCsrfToken()
            },
            success: function(response) {
                if (response.success) {
                    if (!goal.id) {
                        goal.id = response.goal.id;
                    }
                    alert('Цель успешно сохранена!');
                } else {
                    alert('Ошибка: ' + response.message);
                }
            },
            error: function() {
                alert('Ошибка при сохранении цели');
            }
        });
    });
    
    // Удаление цели
    $('.delete-goal-btn').off('click').on('click', function() {
        if (!confirm('Вы уверены, что хотите удалить эту цель?')) {
            return;
        }
        
        var goalId = $(this).data('goal-id');
        if (!goalId) {
            // Новая цель, просто удаляем из массива
            var goalItem = $(this).closest('.goal-item');
            var stageId = goalItem.data('stage-id');
            var stageIndex = stages.findIndex(function(s) { return s.id === stageId; });
            if (stageIndex !== -1) {
                var goalIndex = stages[stageIndex].goals.findIndex(function(g) { return !g.id; });
                if (goalIndex !== -1) {
                    stages[stageIndex].goals.splice(goalIndex, 1);
                    renderStages();
                }
            }
            return;
        }
        
        $.ajax({
            url: deleteGoalUrl,
            method: 'POST',
            data: {
                id: goalId,
                [csrfParam]: csrfToken
            },
            success: function(response) {
                window.location.reload();
            },
            error: function() {
                alert('Ошибка при удалении цели');
            }
        });
    });
}

$(document).ready(function() {
    renderStages();
    
    // Обработчик для кнопки добавления этапа (используем делегирование для надежности)
    $(document).on('click', '#add-stage-btn', function(e) {
        e.preventDefault();
        var newStage = {
            id: null,
            name: '',
            start_month: 0,
            end_month: 0,
            description: '',
            goals: []
        };
        stages.push(newStage);
        renderStages();
    });
});
JS;

$this->registerJs($js);
?>
<div class="roadmap-update">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= Html::encode($this->title) ?></h1>
        <div>
            <?= Html::a('Назад к просмотру', ['index'], ['class' => 'btn btn-secondary']) ?>
            <button type="button" class="btn btn-success" id="add-stage-btn">+ Добавить этап</button>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div id="stages-container">
                <!-- Этапы будут вставлены здесь через JavaScript -->
            </div>
        </div>
    </div>

</div>

