<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use app\models\Project;
use app\models\User;

/** @var yii\web\View $this */
/** @var app\models\Project $model */
/** @var yii\widgets\ActiveForm $form */

$searchUrl = Url::to(['global-project/search-managers']);
$selectedManagers = [];
if (!empty($model->manager_ids)) {
    foreach ($model->manager_ids as $id) {
        if ($id instanceof \MongoDB\BSON\ObjectId) {
            $selectedManagers[] = (string)$id;
        } elseif (is_string($id)) {
            $selectedManagers[] = $id;
        }
    }
} elseif ($model->manager_id) {
    $selectedManagers[] = (string)$model->manager_id;
}

// Получаем данные о выбранных руководителях для отображения
$selectedManagersData = [];
if (!empty($selectedManagers)) {
    foreach ($selectedManagers as $managerId) {
        $user = User::findOne(['_id' => new \MongoDB\BSON\ObjectId($managerId)]);
        if ($user) {
            $selectedManagersData[] = [
                'id' => (string)$user->_id,
                'text' => $user->fio . ' (' . $user->email . ')',
                'role' => [
                    User::ROLE_ADMIN => 'Админ',
                    User::ROLE_RECTOR => 'Ректор',
                    User::ROLE_HEAD => 'Руководитель',
                    User::ROLE_TOP_MANAGER => 'Топ-менеджер',
                    User::ROLE_MANAGER => 'Менеджер',
                ][$user->role] ?? $user->role,
            ];
        }
    }
}
?>

<?php $form = ActiveForm::begin(); ?>

<div class="row">
    <div class="col-md-8">
        <?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>

        <?= $form->field($model, 'description')->textarea(['rows' => 6]) ?>

        <?= $form->field($model, 'goals')->textarea(['rows' => 4]) ?>
    </div>

    <div class="col-md-4">
        <?= $form->field($model, 'status')->dropDownList([
            Project::STATUS_DRAFT => 'Черновик',
            Project::STATUS_ACTIVE => 'Активный',
            Project::STATUS_REVIEW => 'На проверке',
            Project::STATUS_FINISHED => 'Завершен',
            Project::STATUS_FROZEN => 'Заморожен',
        ]) ?>

        <div class="form-group field-project-manager_ids managers-search-wrapper">
            <label class="control-label">Руководители</label>
            <div class="managers-search-container">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" 
                           id="manager-search" 
                           class="form-control" 
                           placeholder="Введите минимум 3 символа для поиска..."
                           autocomplete="off">
                    <button type="button" 
                            class="btn btn-primary" 
                            id="search-manager-button"
                            title="Найти руководителей">
                        <i class="fas fa-search"></i> Найти
                    </button>
                    <span class="input-group-text search-loader" style="display: none;">
                        <i class="fas fa-spinner fa-spin"></i>
                    </span>
                </div>
                <div id="manager-search-results" class="manager-search-results"></div>
                <div id="selected-managers-list" class="selected-managers-list mt-2">
                    <?php foreach ($selectedManagersData as $manager): ?>
                        <span class="badge bg-primary fs-6 p-2 me-2 mb-2 selected-manager-badge" data-manager-id="<?= Html::encode($manager['id']) ?>">
                            <i class="fas fa-user me-2"></i>
                            <?= Html::encode($manager['text']) ?>
                            <?php if (!empty($manager['role'])): ?>
                                <span class="badge bg-light text-dark ms-1"><?= Html::encode($manager['role']) ?></span>
                            <?php endif; ?>
                            <button type="button" class="btn-close btn-close-white ms-2 remove-manager" style="font-size: 0.7em;"></button>
                        </span>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" name="Project[manager_ids_array]" id="manager-ids-input" value="<?= implode(',', $selectedManagers) ?>">
                <div class="help-block"></div>
            </div>
        </div>

        <?= $form->field($model, 'start_date_str')->textInput(['type' => 'date']) ?>

        <?= $form->field($model, 'end_date_str')->textInput(['type' => 'date']) ?>
    </div>
</div>

<div class="form-group">
    <?= Html::submitButton('Сохранить', ['class' => 'nku-btn nku-btn--primary']) ?>
    <?= Html::a('Отмена', ['view', 'id' => (string)$model->_id], ['class' => 'nku-btn nku-btn--secondary']) ?>
</div>

<?php ActiveForm::end(); ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var searchInput = document.getElementById('manager-search');
    var resultsDiv = document.getElementById('manager-search-results');
    var managerIdsInput = document.getElementById('manager-ids-input');
    var selectedManagersList = document.getElementById('selected-managers-list');
    var loader = document.querySelector('.search-loader');
    var searchButton = document.getElementById('search-manager-button');
    var searchTimeout;
    var searchUrl = '<?= $searchUrl ?>';
    var minLength = 3;
    var selectedManagers = <?= json_encode($selectedManagers) ?>;
    
    // Функция для обновления скрытого поля с ID руководителей
    function updateManagerIdsInput() {
        var ids = [];
        var badges = selectedManagersList.querySelectorAll('.selected-manager-badge');
        badges.forEach(function(badge) {
            var managerId = badge.getAttribute('data-manager-id');
            if (managerId) {
                ids.push(managerId);
            }
        });
        managerIdsInput.value = ids.join(',');
    }
    
    // Инициализация: обновляем скрытое поле при загрузке
    updateManagerIdsInput();
    
    // Обработчик клика на кнопку поиска
    if (searchButton) {
        searchButton.addEventListener('click', function(e) {
            e.preventDefault();
            var query = searchInput.value.trim();
            
            if (query.length < minLength) {
                resultsDiv.innerHTML = '<div class="manager-search-hint">Введите минимум ' + minLength + ' символа для поиска</div>';
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
            if (loader) loader.style.display = 'none';
            resultsDiv.innerHTML = '';
            
            if (response && response.results !== undefined) {
                if (response.results.length > 0) {
                    response.results.forEach(function(manager) {
                        if (manager && manager.id && manager.text) {
                            // Проверяем, не выбран ли уже этот руководитель
                            var isSelected = selectedManagersList.querySelector('[data-manager-id="' + manager.id + '"]') !== null;
                            
                            var item = document.createElement('div');
                            item.className = 'manager-search-item' + (isSelected ? ' selected' : '');
                            var roleBadge = manager.role ? '<span class="badge bg-secondary ms-2">' + manager.role + '</span>' : '';
                            item.innerHTML = '<i class="fas fa-user me-2"></i>' + manager.text + roleBadge + (isSelected ? ' <span class="text-muted">(уже выбран)</span>' : '');
                            item.setAttribute('data-manager-id', manager.id);
                            
                            if (!isSelected) {
                                item.addEventListener('click', function(e) {
                                    e.preventDefault();
                                    addManager(manager.id, manager.text, manager.role);
                                });
                            }
                            
                            resultsDiv.appendChild(item);
                        }
                    });
                    if (resultsDiv.children.length > 0) {
                        resultsDiv.style.display = 'block';
                    } else {
                        resultsDiv.innerHTML = '<div class="manager-search-empty">Руководители не найдены</div>';
                        resultsDiv.style.display = 'block';
                    }
                } else {
                    resultsDiv.innerHTML = '<div class="manager-search-empty">Руководители не найдены</div>';
                    resultsDiv.style.display = 'block';
                }
            } else {
                resultsDiv.innerHTML = '<div class="manager-search-error">Ошибка: неверный формат ответа от сервера</div>';
                resultsDiv.style.display = 'block';
            }
        })
        .catch(function(error) {
            if (loader) loader.style.display = 'none';
            console.error('Search error:', error);
            resultsDiv.innerHTML = '<div class="manager-search-error">Ошибка при поиске. Попробуйте еще раз.</div>';
            resultsDiv.style.display = 'block';
        });
    }
    
    // Функция добавления руководителя
    function addManager(managerId, managerText, managerRole) {
        // Проверяем, не выбран ли уже этот руководитель
        if (selectedManagersList.querySelector('[data-manager-id="' + managerId + '"]')) {
            return;
        }
        
        // Создаем badge для выбранного руководителя
        var badge = document.createElement('span');
        badge.className = 'badge bg-primary fs-6 p-2 me-2 mb-2 selected-manager-badge';
        badge.setAttribute('data-manager-id', managerId);
        var roleBadge = managerRole ? '<span class="badge bg-light text-dark ms-1">' + managerRole + '</span>' : '';
        badge.innerHTML = '<i class="fas fa-user me-2"></i>' + managerText + roleBadge + 
            ' <button type="button" class="btn-close btn-close-white ms-2 remove-manager" style="font-size: 0.7em;"></button>';
        
        selectedManagersList.appendChild(badge);
        
        // Очищаем поле поиска и скрываем результаты
        searchInput.value = '';
        resultsDiv.style.display = 'none';
        resultsDiv.innerHTML = '';
        
        // Обновляем скрытое поле
        updateManagerIdsInput();
        
        // Обновляем результаты поиска, если они открыты
        if (resultsDiv.style.display === 'block') {
            var query = searchInput.value.trim();
            if (query.length >= minLength) {
                performSearch(query);
            }
        }
    }
    
    // Функция удаления руководителя
    function removeManager(managerId) {
        var badge = selectedManagersList.querySelector('[data-manager-id="' + managerId + '"]');
        if (badge) {
            badge.remove();
            updateManagerIdsInput();
            
            // Обновляем результаты поиска, если они открыты
            if (resultsDiv.style.display === 'block') {
                var query = searchInput.value.trim();
                if (query.length >= minLength) {
                    performSearch(query);
                }
            }
        }
    }
    
    // Обработчик удаления руководителя (делегирование событий)
    selectedManagersList.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('remove-manager')) {
            e.preventDefault();
            var badge = e.target.closest('.selected-manager-badge');
            if (badge) {
                var managerId = badge.getAttribute('data-manager-id');
                removeManager(managerId);
            }
        }
    });
    
    // Обработчик ввода текста - автоматический поиск
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            var query = this.value.trim();
            
            clearTimeout(searchTimeout);
            
            // Показываем подсказку, если символов меньше минимума
            if (query.length > 0 && query.length < minLength) {
                resultsDiv.innerHTML = '<div class="manager-search-hint">Введите еще ' + (minLength - query.length) + ' символов для поиска</div>';
                resultsDiv.style.display = 'block';
                return;
            }
            
            if (query.length === 0) {
                resultsDiv.style.display = 'none';
                resultsDiv.innerHTML = '';
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
        var container = document.querySelector('.managers-search-container');
        if (container && !container.contains(e.target)) {
            resultsDiv.style.display = 'none';
        }
    });
    
    // Навигация по результатам клавиатурой
    if (searchInput) {
        searchInput.addEventListener('keydown', function(e) {
            var items = resultsDiv.querySelectorAll('.manager-search-item:not(.selected)');
            
            if (e.keyCode === 40) { // Стрелка вниз
                e.preventDefault();
                var active = resultsDiv.querySelector('.manager-search-item.active');
                if (active) {
                    active.classList.remove('active');
                    var next = active.nextElementSibling;
                    if (next && !next.classList.contains('selected')) {
                        next.classList.add('active');
                    } else if (items.length > 0) {
                        items[0].classList.add('active');
                    }
                } else if (items.length > 0) {
                    items[0].classList.add('active');
                }
            } else if (e.keyCode === 38) { // Стрелка вверх
                e.preventDefault();
                var active = resultsDiv.querySelector('.manager-search-item.active');
                if (active) {
                    active.classList.remove('active');
                    var prev = active.previousElementSibling;
                    if (prev && !prev.classList.contains('selected')) {
                        prev.classList.add('active');
                    } else if (items.length > 0) {
                        items[items.length - 1].classList.add('active');
                    }
                } else if (items.length > 0) {
                    items[items.length - 1].classList.add('active');
                }
            } else if (e.keyCode === 13) { // Enter
                e.preventDefault();
                var active = resultsDiv.querySelector('.manager-search-item.active:not(.selected)');
                if (active) {
                    var managerId = active.getAttribute('data-manager-id');
                    var managerText = active.textContent.trim().replace(/^\s*[^\s]+\s+/, '').replace(/\s*\(уже выбран\)\s*$/, '');
                    if (managerId) {
                        // Извлекаем роль из текста, если есть
                        var roleMatch = managerText.match(/\(([^)]+)\)/);
                        var role = null;
                        if (roleMatch) {
                            managerText = managerText.replace(/\s*\([^)]+\)\s*$/, '');
                        }
                        addManager(managerId, managerText, role);
                    }
                } else {
                    // Если ничего не выбрано, но есть результаты - выбираем первый доступный
                    if (items.length > 0) {
                        var firstItem = items[0];
                        var managerId = firstItem.getAttribute('data-manager-id');
                        var managerText = firstItem.textContent.trim().replace(/^\s*[^\s]+\s+/, '').replace(/\s*\(уже выбран\)\s*$/, '');
                        if (managerId) {
                            addManager(managerId, managerText, null);
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
});
</script>

<style>
.managers-search-wrapper {
    margin-bottom: 1.5rem;
}

.managers-search-container {
    position: relative;
}

.managers-search-container .input-group {
    margin-bottom: 0;
}

.managers-search-container .input-group-text {
    background-color: #f8f9fa;
    border-color: #dee2e6;
}

#manager-search {
    border-left: none;
    border-right: none;
}

#manager-search:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

#search-manager-button {
    border-left: none;
    white-space: nowrap;
    z-index: 0;
}

#search-manager-button:hover {
    z-index: 1;
}

.search-loader {
    background-color: #f8f9fa;
}

#manager-search-results {
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

.manager-search-item {
    padding: 0.75rem 1rem;
    cursor: pointer;
    border-bottom: 1px solid #f0f0f0;
    transition: background-color 0.2s;
    color: #212529;
}

.manager-search-item:last-child {
    border-bottom: none;
}

.manager-search-item:hover:not(.selected),
.manager-search-item.active:not(.selected) {
    background-color: #e7f1ff;
    color: #0d6efd;
}

.manager-search-item.selected {
    background-color: #f8f9fa;
    color: #6c757d;
    cursor: not-allowed;
}

.manager-search-item i {
    color: #6c757d;
}

.manager-search-item:hover:not(.selected) i,
.manager-search-item.active:not(.selected) i {
    color: #0d6efd;
}

.manager-search-hint {
    padding: 0.75rem 1rem;
    color: #6c757d;
    font-style: italic;
    text-align: center;
}

.manager-search-empty {
    padding: 1rem;
    color: #6c757d;
    text-align: center;
}

.manager-search-error {
    padding: 0.75rem 1rem;
    color: #dc3545;
    text-align: center;
}

.selected-managers-list {
    min-height: 40px;
}

.selected-manager-badge {
    display: inline-flex;
    align-items: center;
    font-weight: 500;
}

.selected-manager-badge .btn-close {
    opacity: 0.8;
}

.selected-manager-badge .btn-close:hover {
    opacity: 1;
}
</style>