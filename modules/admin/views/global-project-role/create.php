<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use app\models\GlobalProjectRole;
use app\models\User;

/** @var yii\web\View $this */
/** @var app\models\GlobalProjectRole $model */

$this->title = 'Назначить роль в глобальном проекте';
$this->params['breadcrumbs'][] = ['label' => 'Админка', 'url' => ['/admin']];
$this->params['breadcrumbs'][] = ['label' => 'Роли в глобальном проекте', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$searchUrl = Url::to(['/admin/global-project-role/search-users']);
$selectedUser = null;
if ($model->user_id) {
    $selectedUser = User::findOne(['_id' => $model->user_id]);
}
?>

<div class="global-project-role-create">

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
                           autocomplete="off">
                    <button type="button" 
                            class="btn btn-primary" 
                            id="search-button"
                            title="Найти пользователей">
                        <i class="fas fa-search"></i> Найти
                    </button>
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
            GlobalProjectRole::ROLE_RECTOR => 'Ректор',
            GlobalProjectRole::ROLE_GLOBAL_MANAGER => 'Глобальный менеджер',
            GlobalProjectRole::ROLE_GLOBAL_EXECUTOR => 'Глобальный исполнитель',
        ], ['prompt' => 'Выберите роль']) ?>

        <div class="form-group">
            <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
            <?= Html::a('Отмена', ['index'], ['class' => 'btn btn-secondary']) ?>
        </div>

        <?php ActiveForm::end(); ?>

    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var searchInput = document.getElementById('user-search');
    var resultsDiv = document.getElementById('user-search-results');
    var userIdInput = document.getElementById('user-id-input');
    var loader = document.querySelector('.search-loader');
    var searchButton = document.getElementById('search-button');
    var searchTimeout;
    var searchUrl = '<?= $searchUrl ?>';
    var minLength = 3;
    
    console.log('Search URL:', searchUrl);
    
    // Обработчик клика на кнопку поиска
    if (searchButton) {
        searchButton.addEventListener('click', function(e) {
            e.preventDefault();
            var query = searchInput.value.trim();
            
            if (query.length < minLength) {
                resultsDiv.innerHTML = '<div class="user-search-hint">Введите минимум ' + minLength + ' символа для поиска</div>';
                resultsDiv.style.display = 'block';
                searchInput.focus();
                return;
            }
            
            performSearch(query);
        });
    }
    
    // Функция для выполнения поиска
    function performSearch(query) {
        if (!query || query.length < minLength) {
            resultsDiv.style.display = 'none';
            resultsDiv.innerHTML = '';
            if (loader) loader.style.display = 'none';
            return;
        }
        
        if (loader) loader.style.display = 'block';
        resultsDiv.style.display = 'none';
        resultsDiv.innerHTML = '';
        
        console.log('Performing search with URL:', searchUrl, 'Query:', query);
        
        var url = searchUrl + '?q=' + encodeURIComponent(query);
        fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
            }
        })
        .then(function(response) {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(function(response) {
            console.log('Search response received:', response);
            if (loader) loader.style.display = 'none';
            resultsDiv.innerHTML = '';
            
            if (response && response.results !== undefined) {
                if (response.results.length > 0) {
                    response.results.forEach(function(user) {
                        if (user && user.id && user.text) {
                            var item = document.createElement('div');
                            item.className = 'user-search-item';
                            item.innerHTML = '<i class="fas fa-user me-2"></i>' + user.text;
                            item.setAttribute('data-user-id', user.id);
                            item.addEventListener('click', function(e) {
                                e.preventDefault();
                                selectUser(user.id, user.text);
                            });
                            resultsDiv.appendChild(item);
                        }
                    });
                    if (resultsDiv.children.length > 0) {
                        resultsDiv.style.display = 'block';
                    } else {
                        resultsDiv.innerHTML = '<div class="user-search-empty">Пользователи не найдены</div>';
                        resultsDiv.style.display = 'block';
                    }
                } else {
                    resultsDiv.innerHTML = '<div class="user-search-empty">Пользователи не найдены</div>';
                    resultsDiv.style.display = 'block';
                }
            } else {
                console.error('Invalid response format:', response);
                resultsDiv.innerHTML = '<div class="user-search-error">Ошибка: неверный формат ответа от сервера</div>';
                resultsDiv.style.display = 'block';
            }
        })
        .catch(function(error) {
            if (loader) loader.style.display = 'none';
            console.error('Search error:', error);
            resultsDiv.innerHTML = '<div class="user-search-error">Ошибка при поиске. Попробуйте еще раз.</div>';
            resultsDiv.style.display = 'block';
        });
    }
    
    // Функция выбора пользователя
    function selectUser(userId, userText) {
        userIdInput.value = userId;
        searchInput.value = userText;
        resultsDiv.style.display = 'none';
        
        // Удаляем старый badge
        var oldBadge = document.querySelector('.selected-user-badge');
        if (oldBadge) {
            oldBadge.remove();
        }
        
        // Создаем новый badge
        var badgeDiv = document.createElement('div');
        badgeDiv.className = 'mt-2 selected-user-badge';
        badgeDiv.innerHTML = '<span class="badge bg-primary fs-6 p-2">' +
            '<i class="fas fa-user me-2"></i>' +
            userText +
            ' <button type="button" class="btn-close btn-close-white ms-2" id="clear-user" style="font-size: 0.7em;"></button>' +
            '</span>';
        
        var inputGroup = searchInput.closest('.input-group');
        if (inputGroup && inputGroup.parentNode) {
            inputGroup.parentNode.insertBefore(badgeDiv, inputGroup.nextSibling);
        }
        
        // Привязываем обработчик очистки
        var clearBtn = document.getElementById('clear-user');
        if (clearBtn) {
            clearBtn.addEventListener('click', function(e) {
                e.preventDefault();
                clearSelection();
            });
        }
    }
    
    // Функция очистки выбора
    function clearSelection() {
        userIdInput.value = '';
        searchInput.value = '';
        var badge = document.querySelector('.selected-user-badge');
        if (badge) {
            badge.remove();
        }
        resultsDiv.style.display = 'none';
        searchInput.focus();
    }
    
    // Обработчик ввода текста - автоматический поиск
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            var query = this.value.trim();
            
            clearTimeout(searchTimeout);
            
            // Если поле очищено, сбрасываем выбор
            if (query.length === 0) {
                userIdInput.value = '';
                var badge = document.querySelector('.selected-user-badge');
                if (badge) badge.remove();
                resultsDiv.style.display = 'none';
                resultsDiv.innerHTML = '';
                return;
            }
            
            // Показываем подсказку, если символов меньше минимума
            if (query.length > 0 && query.length < minLength) {
                resultsDiv.innerHTML = '<div class="user-search-hint">Введите еще ' + (minLength - query.length) + ' символов для поиска</div>';
                resultsDiv.style.display = 'block';
                return;
            }
            
            // Автоматически выполняем поиск с небольшой задержкой
            searchTimeout = setTimeout(function() {
                performSearch(query);
            }, 250);
        });
        
        // Обработка фокуса - показываем результаты, если есть текст
        searchInput.addEventListener('focus', function() {
            var query = this.value.trim();
            if (query.length >= minLength) {
                if (resultsDiv.children.length === 0) {
                    performSearch(query);
                } else {
                    resultsDiv.style.display = 'block';
                }
            }
        });
    }
    
    // Скрываем результаты при клике вне области поиска
    document.addEventListener('click', function(e) {
        var container = document.querySelector('.user-search-container');
        if (container && !container.contains(e.target)) {
            resultsDiv.style.display = 'none';
        }
    });
    
    // Очистка выбранного пользователя (делегирование событий)
    document.addEventListener('click', function(e) {
        if (e.target && e.target.id === 'clear-user') {
            e.preventDefault();
            clearSelection();
        }
    });
    
    // Навигация по результатам клавиатурой
    if (searchInput) {
        searchInput.addEventListener('keydown', function(e) {
            var items = resultsDiv.querySelectorAll('.user-search-item');
            
            if (e.keyCode === 40) { // Стрелка вниз
                e.preventDefault();
                var active = resultsDiv.querySelector('.user-search-item.active');
                if (active) {
                    active.classList.remove('active');
                    var next = active.nextElementSibling;
                    if (next) {
                        next.classList.add('active');
                    } else if (items.length > 0) {
                        items[0].classList.add('active');
                    }
                } else if (items.length > 0) {
                    items[0].classList.add('active');
                }
            } else if (e.keyCode === 38) { // Стрелка вверх
                e.preventDefault();
                var active = resultsDiv.querySelector('.user-search-item.active');
                if (active) {
                    active.classList.remove('active');
                    var prev = active.previousElementSibling;
                    if (prev) {
                        prev.classList.add('active');
                    } else if (items.length > 0) {
                        items[items.length - 1].classList.add('active');
                    }
                } else if (items.length > 0) {
                    items[items.length - 1].classList.add('active');
                }
            } else if (e.keyCode === 13) { // Enter
                e.preventDefault();
                var active = resultsDiv.querySelector('.user-search-item.active');
                if (active) {
                    var userId = active.getAttribute('data-user-id');
                    var userText = active.textContent.trim().replace(/^\s*[^\s]+\s+/, ''); // Убираем иконку
                    if (userId) {
                        selectUser(userId, userText);
                    }
                } else {
                    // Если ничего не выбрано, но есть результаты - выбираем первый
                    if (items.length > 0) {
                        var firstItem = items[0];
                        var userId = firstItem.getAttribute('data-user-id');
                        var userText = firstItem.textContent.trim().replace(/^\s*[^\s]+\s+/, '');
                        if (userId) {
                            selectUser(userId, userText);
                        }
                    } else {
                        // Если результатов нет, запускаем поиск
                        var query = searchInput.value.trim();
                        if (query.length >= minLength) {
                            performSearch(query);
                        }
                    }
                }
            } else if (e.keyCode === 27) { // Escape
                e.preventDefault();
                resultsDiv.style.display = 'none';
            }
        });
    }
    
    // Предотвращаем отправку формы по Enter, если пользователь не выбран
    var form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            var userId = userIdInput.value;
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
    }
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
    border-right: none;
}

#user-search:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

#search-button {
    border-left: none;
    white-space: nowrap;
    z-index: 0;
}

#search-button:hover {
    z-index: 1;
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

