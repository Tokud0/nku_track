<?php

use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var app\models\Roadmap $roadmap */
/** @var app\models\RoadmapStage[] $stages */

$this->title = 'Редактирование глобальной дорожной карты';
$this->params['breadcrumbs'][] = ['label' => 'Глобальный проект', 'url' => ['/global-project/index']];
$this->params['breadcrumbs'][] = ['label' => 'Глобальная дорожная карта', 'url' => ['view']];
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
    $startDate = '';
    $endDate = '';
    if ($stage->start_date instanceof \MongoDB\BSON\UTCDateTime) {
        $startDate = date('Y-m-d', $stage->start_date->toDateTime()->getTimestamp());
    }
    if ($stage->end_date instanceof \MongoDB\BSON\UTCDateTime) {
        $endDate = date('Y-m-d', $stage->end_date->toDateTime()->getTimestamp());
    }
    return [
        'id' => (string)$stage->_id,
        'name' => $stage->name,
        'start_month' => $stage->start_month,
        'end_month' => $stage->end_month,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'description' => $stage->description,
        'is_completed' => !empty($stage->is_completed),
        'completion_format' => $stage->completion_format ?? '',
        'goals' => $goals,
    ];
}, $stages));

$saveStageUrl = Url::to(['save-stage']);
$saveGoalUrl = Url::to(['save-goal']);
$completeStageUrl = Url::to(['complete-stage']);
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
var completeStageUrl = '$completeStageUrl';
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
        stageHtml += '<h5 class="mb-0">Этап ' + (stageIndex + 1) + ': <span class="stage-name-display">' + escapeHtml(stage.name) + '</span>';
        if (stage.is_completed) {
            stageHtml += ' <span class="badge bg-success ms-2">Завершен</span>';
        }
        stageHtml += '</h5>';
        stageHtml += '<div>';
        if (!stage.is_completed && stage.id) {
            stageHtml += '<button type="button" class="btn btn-sm btn-success me-2 complete-stage-btn" data-stage-id="' + stage.id + '">Завершить этап</button>';
        }
        stageHtml += '<button type="button" class="btn btn-sm btn-danger delete-stage-btn" data-stage-id="' + stage.id + '">Удалить этап</button>';
        stageHtml += '</div>';
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
        stageHtml += '<div class="row mb-3">';
        stageHtml += '<div class="col-md-6">';
        stageHtml += '<label>Дата начала этапа</label>';
        stageHtml += '<input type="date" class="form-control stage-start-date" value="' + (stage.start_date || '') + '">';
        stageHtml += '</div>';
        stageHtml += '<div class="col-md-6">';
        stageHtml += '<label>Дата окончания этапа</label>';
        stageHtml += '<input type="date" class="form-control stage-end-date" value="' + (stage.end_date || '') + '">';
        stageHtml += '</div>';
        stageHtml += '</div>';
        stageHtml += '<div class="mb-3">';
        stageHtml += '<label>Описание этапа</label>';
        stageHtml += '<textarea class="form-control stage-description" rows="3">' + escapeHtml(stage.description || '') + '</textarea>';
        stageHtml += '</div>';
        if (stage.is_completed && stage.completion_format) {
            stageHtml += '<div class="alert alert-success mb-3">';
            stageHtml += '<strong>Этап завершен</strong><br>';
            stageHtml += '<small>Формат завершения: ' + escapeHtml(stage.completion_format) + '</small>';
            stageHtml += '</div>';
        }
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
        stage.start_date = stageItem.find('.stage-start-date').val() || '';
        stage.end_date = stageItem.find('.stage-end-date').val() || '';
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
                start_date: stage.start_date,
                end_date: stage.end_date,
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
    
    // Завершение этапа
    $('.complete-stage-btn').off('click').on('click', function() {
        var stageId = $(this).data('stage-id');
        if (!stageId) return;
        
        $('#complete-stage-id').val(stageId);
        $('#completion-format').val('');
        var completeModal = new bootstrap.Modal(document.getElementById('completeStageModal'));
        completeModal.show();
    });
    
    // Подтверждение завершения этапа
    $('#confirm-complete-stage-btn').off('click').on('click', function() {
        var stageId = $('#complete-stage-id').val();
        var completionFormat = $('#completion-format').val().trim();
        
        if (!stageId) {
            showNotification('Ошибка: этап не выбран', 'danger');
            return;
        }
        
        if (!completionFormat) {
            showNotification('Пожалуйста, укажите формат завершения', 'danger');
            return;
        }
        
        var confirmBtn = $(this);
        var originalText = confirmBtn.text();
        confirmBtn.prop('disabled', true).text('Сохранение...');
        
        $.ajax({
            url: completeStageUrl,
            method: 'POST',
            dataType: 'json',
            data: {
                id: stageId,
                completion_format: completionFormat,
                [csrfParam]: csrfToken
            },
            success: function(response) {
                if (response && response.success) {
                    // Обновляем этап в массиве
                    var stageIndex = stages.findIndex(function(s) { return s.id === stageId; });
                    if (stageIndex !== -1) {
                        stages[stageIndex].is_completed = true;
                        stages[stageIndex].completion_format = completionFormat;
                    }
                    
                    // Закрываем модальное окно
                    var completeModal = bootstrap.Modal.getInstance(document.getElementById('completeStageModal'));
                    if (completeModal) {
                        completeModal.hide();
                    }
                    
                    // Перерисовываем этапы
                    renderStages();
                    
                    showNotification('Этап успешно завершен!', 'success');
                } else {
                    var errorMsg = (response && response.message) ? response.message : 'Неизвестная ошибка';
                    showNotification('Ошибка: ' + errorMsg, 'danger');
                }
            },
            error: function(xhr, status, error) {
                var errorMsg = 'Ошибка при завершении этапа';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg += ': ' + xhr.responseJSON.message;
                }
                showNotification(errorMsg, 'danger');
            },
            complete: function() {
                confirmBtn.prop('disabled', false).text(originalText);
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
            start_date: '',
            end_date: '',
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
            <?= Html::a('Назад к просмотру', ['view'], ['class' => 'btn btn-secondary']) ?>
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

    <!-- Модальное окно для завершения этапа -->
    <div class="modal fade" id="completeStageModal" tabindex="-1" aria-labelledby="completeStageModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="completeStageModalLabel">Завершение этапа</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="complete-stage-form">
                        <div class="mb-3">
                            <label for="completion-format" class="form-label">Формат завершения</label>
                            <textarea class="form-control" id="completion-format" rows="4" placeholder="Опишите формат завершения этапа..." required></textarea>
                            <small class="form-text text-muted">Укажите, в каком формате завершен этап</small>
                        </div>
                        <input type="hidden" id="complete-stage-id" value="">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="button" class="btn btn-success" id="confirm-complete-stage-btn">Завершить</button>
                </div>
            </div>
        </div>
    </div>

</div>

