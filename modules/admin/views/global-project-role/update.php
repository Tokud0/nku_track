<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use app\models\GlobalProjectRole;
use app\models\User;

/** @var yii\web\View $this */
/** @var app\models\GlobalProjectRole $model */

$this->title = 'Редактировать роль в глобальном проекте';
$this->params['breadcrumbs'][] = ['label' => 'Админка', 'url' => ['/admin']];
$this->params['breadcrumbs'][] = ['label' => 'Роли в глобальном проекте', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->user ? $model->user->fio : 'Роль', 'url' => ['view', 'id' => (string)$model->_id]];
$this->params['breadcrumbs'][] = 'Редактировать';

$searchUrl = Url::to(['search-users']);
$selectedUser = null;
if ($model->user_id) {
    $selectedUser = User::findOne(['_id' => $model->user_id]);
}
?>

<div class="global-project-role-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="global-project-role-form">

        <?php $form = ActiveForm::begin(); ?>

        <div class="form-group field-globalprojectrole-user_id user-search-wrapper">
            <label class="control-label" for="user-search">Пользователь</label>
            <div class="user-search-container">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" 
                           id="user-search" 
                           class="form-control" 
                           placeholder="Введите минимум 3 символа для поиска..."
                           value="<?= $selectedUser ? Html::encode($selectedUser->fio . ' (' . $selectedUser->email . ')') : '' ?>"
                           autocomplete="off">
                    <span class="input-group-text search-loader" style="display: none;">
                        <i class="fas fa-spinner fa-spin"></i>
                    </span>
                </div>
                <input type="hidden" name="GlobalProjectRole[user_id]" id="user-id-input" value="<?= $model->user_id ? (string)$model->user_id : '' ?>">
                <div id="user-search-results" class="user-search-results"></div>
                <div class="help-block"></div>
                <?php if ($selectedUser): ?>
                    <div class="mt-2 selected-user-badge">
                        <span class="badge bg-primary fs-6 p-2">
                            <i class="fas fa-user me-2"></i>
                            <?= Html::encode($selectedUser->fio) ?> (<?= Html::encode($selectedUser->email) ?>)
                            <button type="button" class="btn-close btn-close-white ms-2" id="clear-user" style="font-size: 0.7em;"></button>
                        </span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?= $form->field($model, 'role')->dropDownList([
            GlobalProjectRole::ROLE_RECTOR => 'Глобальный руководитель',
            GlobalProjectRole::ROLE_GLOBAL_TOP_MANAGER => 'Глобальный топ-менеджер',
            GlobalProjectRole::ROLE_GLOBAL_MANAGER => 'Глобальный менеджер',
            GlobalProjectRole::ROLE_GLOBAL_EXECUTOR => 'Глобальный исполнитель',
        ], ['prompt' => 'Выберите роль']) ?>

        <div class="form-group">
            <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
            <?= Html::a('Отмена', ['view', 'id' => (string)$model->_id], ['class' => 'btn btn-secondary']) ?>
        </div>

        <?php ActiveForm::end(); ?>

    </div>

</div>

<script>
jQuery(document).ready(function($) {
    var searchInput = $('#user-search');
    var resultsDiv = $('#user-search-results');
    var userIdInput = $('#user-id-input');
    var loader = $('.search-loader');
    var searchTimeout;
    var searchUrl = '<?= $searchUrl ?>';
    var minLength = 3;
    
    // Функция для выполнения поиска
    function performSearch(query) {
        if (!query || query.length < minLength) {
            resultsDiv.hide().empty();
            loader.hide();
            return;
        }
        
        loader.show();
        resultsDiv.hide();
        
        $.ajax({
            url: searchUrl,
            method: 'GET',
            data: { q: query },
            dataType: 'json',
            success: function(response) {
                loader.hide();
                resultsDiv.empty();
                
                if (response.results && response.results.length > 0) {
                    response.results.forEach(function(user) {
                        var item = $('<div class="user-search-item">')
                            .html('<i class="fas fa-user me-2"></i>' + user.text)
                            .data('user-id', user.id)
                            .on('click', function(e) {
                                e.preventDefault();
                                selectUser(user.id, user.text);
                            });
                        resultsDiv.append(item);
                    });
                    resultsDiv.show();
                } else {
                    resultsDiv.html('<div class="user-search-empty">Пользователи не найдены</div>');
                    resultsDiv.show();
                }
            },
            error: function(xhr, status, error) {
                loader.hide();
                resultsDiv.empty().html('<div class="user-search-error">Ошибка при поиске. Попробуйте еще раз.</div>');
                resultsDiv.show();
            }
        });
    }
    
    // Функция выбора пользователя
    function selectUser(userId, userText) {
        userIdInput.val(userId);
        searchInput.val(userText);
        resultsDiv.hide();
        
        // Удаляем старый badge
        $('.selected-user-badge').remove();
        
        // Создаем новый badge
        var badgeHtml = '<div class="mt-2 selected-user-badge">' +
            '<span class="badge bg-primary fs-6 p-2">' +
            '<i class="fas fa-user me-2"></i>' +
            userText +
            ' <button type="button" class="btn-close btn-close-white ms-2" id="clear-user" style="font-size: 0.7em;"></button>' +
            '</span>' +
            '</div>';
        
        searchInput.closest('.input-group').after(badgeHtml);
        
        // Привязываем обработчик очистки
        $('#clear-user').on('click', function(e) {
            e.preventDefault();
            clearSelection();
        });
    }
    
    // Функция очистки выбора
    function clearSelection() {
        userIdInput.val('');
        searchInput.val('');
        $('.selected-user-badge').remove();
        resultsDiv.hide();
        searchInput.focus();
    }
    
    // Обработчик ввода текста - автоматический поиск
    searchInput.on('input', function() {
        var query = $(this).val().trim();
        
        clearTimeout(searchTimeout);
        
        // Если поле очищено, сбрасываем выбор
        if (query.length === 0) {
            userIdInput.val('');
            $('.selected-user-badge').remove();
            resultsDiv.hide().empty();
            return;
        }
        
        // Показываем подсказку, если символов меньше минимума
        if (query.length > 0 && query.length < minLength) {
            resultsDiv.html('<div class="user-search-hint">Введите еще ' + (minLength - query.length) + ' символов для поиска</div>');
            resultsDiv.show();
            return;
        }
        
        // Автоматически выполняем поиск с небольшой задержкой
        searchTimeout = setTimeout(function() {
            performSearch(query);
        }, 250);
    });
    
    // Обработка фокуса - показываем результаты, если есть текст
    searchInput.on('focus', function() {
        var query = $(this).val().trim();
        if (query.length >= minLength) {
            if (resultsDiv.children().length === 0) {
                performSearch(query);
            } else {
                resultsDiv.show();
            }
        }
    });
    
    // Скрываем результаты при клике вне области поиска
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.user-search-container').length) {
            resultsDiv.hide();
        }
    });
    
    // Очистка выбранного пользователя
    $(document).on('click', '#clear-user', function(e) {
        e.preventDefault();
        clearSelection();
    });
    
    // Навигация по результатам клавиатурой
    searchInput.on('keydown', function(e) {
        var items = resultsDiv.find('.user-search-item');
        
        if (e.keyCode === 40) { // Стрелка вниз
            e.preventDefault();
            var active = items.filter('.active');
            if (active.length > 0) {
                active.removeClass('active');
                var next = active.next();
                if (next.length > 0) {
                    next.addClass('active');
                } else {
                    items.first().addClass('active');
                }
            } else if (items.length > 0) {
                items.first().addClass('active');
            }
        } else if (e.keyCode === 38) { // Стрелка вверх
            e.preventDefault();
            var active = items.filter('.active');
            if (active.length > 0) {
                active.removeClass('active');
                var prev = active.prev();
                if (prev.length > 0) {
                    prev.addClass('active');
                } else {
                    items.last().addClass('active');
                }
            } else if (items.length > 0) {
                items.last().addClass('active');
            }
        } else if (e.keyCode === 13) { // Enter
            e.preventDefault();
            var active = items.filter('.active');
            if (active.length > 0) {
                var userId = active.data('user-id');
                var userText = active.text().replace(/^\s*[^\s]+\s+/, ''); // Убираем иконку
                if (userId) {
                    selectUser(userId, userText);
                }
            } else {
                // Если ничего не выбрано, но есть результаты - выбираем первый
                if (items.length > 0) {
                    var firstItem = items.first();
                    var userId = firstItem.data('user-id');
                    var userText = firstItem.text().replace(/^\s*[^\s]+\s+/, '');
                    if (userId) {
                        selectUser(userId, userText);
                    }
                }
            }
        } else if (e.keyCode === 27) { // Escape
            e.preventDefault();
            resultsDiv.hide();
        }
    });
    
    // Предотвращаем отправку формы по Enter, если пользователь не выбран
    $('form').on('submit', function(e) {
        var userId = userIdInput.val();
        if (!userId || userId.trim() === '') {
            e.preventDefault();
            alert('Пожалуйста, выберите пользователя из списка результатов поиска.');
            searchInput.focus();
            return false;
        }
        
        // Проверяем, что user_id является валидным ObjectId (24 символа hex)
        if (!/^[0-9a-fA-F]{24}$/.test(userId)) {
            e.preventDefault();
            alert('Ошибка: неверный ID пользователя. Пожалуйста, выберите пользователя из списка.');
            clearSelection();
            searchInput.focus();
            return false;
        }
    });
});
</script>

<style>
.user-search-wrapper {
    margin-bottom: 1.5rem;
}

.user-search-container {
    position: relative;
}

.user-search-container .input-group {
    margin-bottom: 0;
}

.user-search-container .input-group-text {
    background-color: #f8f9fa;
    border-color: #dee2e6;
}

#user-search {
    border-left: none;
}

#user-search:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.search-loader {
    background-color: #f8f9fa;
}

#user-search-results {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    z-index: 1000;
    max-height: 350px;
    overflow-y: auto;
    display: none;
    margin-top: 4px;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    background: white;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.user-search-item {
    padding: 0.75rem 1rem;
    cursor: pointer;
    border-bottom: 1px solid #f0f0f0;
    transition: background-color 0.2s;
    color: #212529;
}

.user-search-item:last-child {
    border-bottom: none;
}

.user-search-item:hover,
.user-search-item.active {
    background-color: #e7f1ff;
    color: #0d6efd;
}

.user-search-item i {
    color: #6c757d;
}

.user-search-item:hover i,
.user-search-item.active i {
    color: #0d6efd;
}

.user-search-hint {
    padding: 0.75rem 1rem;
    color: #6c757d;
    font-style: italic;
    text-align: center;
}

.user-search-empty {
    padding: 1rem;
    color: #6c757d;
    text-align: center;
}

.user-search-error {
    padding: 0.75rem 1rem;
    color: #dc3545;
    text-align: center;
}

.selected-user-badge {
    margin-top: 0.75rem;
}

.selected-user-badge .badge {
    display: inline-flex;
    align-items: center;
    font-weight: 500;
}

.selected-user-badge .btn-close {
    opacity: 0.8;
}

.selected-user-badge .btn-close:hover {
    opacity: 1;
}
</style>

