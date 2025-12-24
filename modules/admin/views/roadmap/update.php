<?php

use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var app\models\Department $department */
/** @var app\models\Roadmap $roadmap */
/** @var app\models\RoadmapStage[] $stages */

$this->title = 'Редактирование дорожной карты: ' . $department->name;
$this->params['breadcrumbs'][] = ['label' => 'Админка', 'url' => ['/admin']];
$this->params['breadcrumbs'][] = ['label' => 'Дорожные карты', 'url' => ['index']];
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
$departmentId = (string)$department->_id;
$csrfToken = Yii::$app->request->csrfToken;
$csrfParam = Yii::$app->request->csrfParam;

$js = <<<JS
var stages = $stagesJson;
var roadmapId = '$roadmapId';
var departmentId = '$departmentId';
var saveStageUrl = '$saveStageUrl';
var saveGoalUrl = '$saveGoalUrl';
var deleteStageUrl = '$deleteStageUrl';
var deleteGoalUrl = '$deleteGoalUrl';
var csrfToken = '$csrfToken';
var csrfParam = '$csrfParam';

// Функция для отображения уведомлений
function showNotification(message, type) {
    type = type || 'info';
    var alertClass = 'alert-' + type;
    var notificationId = 'goal-notification-' + Date.now();
    var notificationHtml = '<div id="' + notificationId + '" class="alert ' + alertClass + ' alert-dismissible fade show" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px; max-width: 400px;">' +
        message +
        '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
        '</div>';
    $('body').append(notificationHtml);
    
    // Автоматически скрываем уведомление через 3 секунды
    setTimeout(function() {
        $('#' + notificationId).fadeOut(300, function() {
            $(this).remove();
        });
    }, 3000);
}

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
        // Если этап уже существует (есть id), показываем "Сохранить изменения этапа", иначе "Сохранить этап"
        var stageButtonText = stage.id ? 'Сохранить изменения этапа' : 'Сохранить этап';
        var stageButtonClass = stage.id ? 'btn-success' : 'btn-primary';
        stageHtml += '<button type="button" class="btn ' + stageButtonClass + ' save-stage-btn" data-stage-id="' + (stage.id || '') + '">' + stageButtonText + '</button>';
        stageHtml += '</div>';
        
        // Цели этапа
        stageHtml += '<hr class="my-4">';
        stageHtml += '<h6>Цели этапа <button type="button" class="btn btn-sm btn-success add-goal-btn" data-stage-id="' + stage.id + '">+ Добавить цель</button></h6>';
        stageHtml += '<div class="goals-container" data-stage-id="' + stage.id + '">';
        
        if (stage.goals && stage.goals.length > 0) {
            stage.goals.forEach(function(goal, goalIndex) {
                // Устанавливаем stage_id для цели, если он не задан
                if (!goal.stage_id) {
                    goal.stage_id = stage.id;
                }
                stageHtml += renderGoal(goal, goalIndex, stage.id);
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

function renderGoal(goal, index, stageId) {
    // Используем переданный stageId или из goal, если он есть
    var goalStageId = stageId || goal.stage_id || '';
    var goalHtml = '<div class="card mb-3 goal-item" data-goal-id="' + (goal.id || '') + '" data-stage-id="' + goalStageId + '">';
    goalHtml += '<div class="card-body">';
    goalHtml += '<div class="d-flex justify-content-between align-items-start mb-2">';
    goalHtml += '<h6 class="mb-0">Цель ' + (index + 1) + '</h6>';
    goalHtml += '<button type="button" class="btn btn-sm btn-danger delete-goal-btn" data-goal-id="' + (goal.id || '') + '">Удалить</button>';
    goalHtml += '</div>';
    goalHtml += '<div class="mb-2">';
    goalHtml += '<label>Название цели</label>';
    goalHtml += '<input type="text" class="form-control goal-title" value="' + escapeHtml(goal.title || '') + '">';
    goalHtml += '</div>';
    goalHtml += '<div class="mb-2">';
    goalHtml += '<label>Описание цели</label>';
    goalHtml += '<textarea class="form-control goal-description" rows="2">' + escapeHtml(goal.description || '') + '</textarea>';
    goalHtml += '</div>';
    // Если цель уже существует (есть id), показываем "Сохранить изменения", иначе "Сохранить цель"
    var buttonText = goal.id ? 'Сохранить изменения' : 'Сохранить цель';
    var buttonClass = goal.id ? 'btn-success' : 'btn-primary';
    goalHtml += '<button type="button" class="btn ' + buttonClass + ' btn-sm save-goal-btn" data-goal-id="' + (goal.id || '') + '">' + buttonText + '</button>';
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
        var saveStageBtn = $(this);
        var stageId = saveStageBtn.data('stage-id');
        var stageItem = saveStageBtn.closest('.stage-item');
        var stageIndex = stages.findIndex(function(s) {
            return (s.id && s.id === stageId) || (!s.id && !stageId);
        });
        
        if (stageIndex === -1) {
            showNotification('Ошибка: этап не найден', 'danger');
            return;
        }
        
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
                        // Обновляем data-stage-id у элемента этапа и кнопки
                        stageItem.attr('data-stage-id', stage.id);
                        saveStageBtn.attr('data-stage-id', stage.id);
                    }
                    stageItem.find('.stage-name-display').text(stage.name);
                    
                    // Обновляем текст кнопки на "Сохранить изменения этапа" и делаем её зеленой
                    saveStageBtn.text('Сохранить изменения этапа').removeClass('btn-primary').addClass('btn-success');
                    
                    // Показываем уведомление об успешном сохранении
                    showNotification('Изменения этапа успешно сохранены!', 'success');
                } else {
                    showNotification('Ошибка: ' + response.message, 'danger');
                }
            },
            error: function() {
                showNotification('Ошибка при сохранении этапа', 'danger');
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
            url: deleteStageUrl + '?id=' + stageId,
            method: 'POST',
            data: {
                id: stageId,
                department_id: departmentId,
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
        var saveBtn = $(this);
        var goalId = saveBtn.data('goal-id');
        var goalItem = saveBtn.closest('.goal-item');
        // Получаем stageId из data-stage-id элемента цели или из родительского контейнера
        var stageId = goalItem.data('stage-id') || goalItem.closest('.goals-container').data('stage-id');
        
        if (!stageId) {
            showNotification('Ошибка: не удалось определить этап', 'danger');
            return;
        }
        
        var stageIndex = stages.findIndex(function(s) { return s.id === stageId; });
        if (stageIndex === -1) {
            showNotification('Ошибка: этап не найден', 'danger');
            return;
        }
        
        var goalIndex = stages[stageIndex].goals.findIndex(function(g) {
            return (g.id && g.id === goalId) || (!g.id && !goalId);
        });
        if (goalIndex === -1) {
            showNotification('Ошибка: цель не найдена в массиве', 'danger');
            return;
        }
        
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
                [csrfParam]: csrfToken
            },
            success: function(response) {
                if (response.success) {
                    if (!goal.id) {
                        goal.id = response.goal.id;
                        // Обновляем data-goal-id у элемента цели и кнопки
                        goalItem.attr('data-goal-id', goal.id);
                        saveBtn.attr('data-goal-id', goal.id);
                    }
                    // Обновляем текст кнопки на "Сохранить изменения" и делаем её зеленой
                    saveBtn.text('Сохранить изменения').removeClass('btn-primary').addClass('btn-success');
                    
                    // Показываем уведомление об успешном сохранении
                    showNotification('Изменения успешно сохранены!', 'success');
                } else {
                    showNotification('Ошибка: ' + response.message, 'danger');
                }
            },
            error: function() {
                showNotification('Ошибка при сохранении цели', 'danger');
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
            url: deleteGoalUrl + '?id=' + goalId,
            method: 'POST',
            data: {
                id: goalId,
                department_id: departmentId,
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
            <?= Html::a('Назад к просмотру', ['view', 'id' => (string)$department->_id], ['class' => 'btn btn-secondary']) ?>
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

