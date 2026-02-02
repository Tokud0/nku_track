<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\Direction;
use app\models\User;

/** @var yii\web\View $this */
/** @var app\models\Direction $model */
/** @var yii\widgets\ActiveForm $form */

// Получаем текущих выбранных руководителей
$selectedManagers = [];
if (!empty($model->manager_ids_array)) {
    foreach ($model->manager_ids_array as $managerId) {
        $user = User::findOne(['_id' => new \MongoDB\BSON\ObjectId($managerId)]);
        if ($user) {
            $selectedManagers[] = [
                'id' => (string)$user->_id,
                'text' => $user->fio . ' (' . $user->email . ')',
            ];
        }
    }
}
?>

<div class="direction-form">
    <?php $form = ActiveForm::begin([
        'id' => 'direction-form',
        'options' => ['class' => 'nku-form'],
    ]); ?>

    <div class="row">
        <div class="col-md-8">
            <?= $form->field($model, 'title', [
                'options' => ['class' => 'mb-4'],
                'inputOptions' => ['class' => 'form-control form-control-lg', 'placeholder' => 'Введите название направления'],
            ])->textInput(['maxlength' => true]) ?>

            <?= $form->field($model, 'description', [
                'options' => ['class' => 'mb-4'],
                'inputOptions' => ['class' => 'form-control', 'rows' => 5, 'placeholder' => 'Опишите направление...'],
            ])->textarea() ?>
        </div>

        <div class="col-md-4">
            <?= $form->field($model, 'status', [
                'options' => ['class' => 'mb-4'],
            ])->dropDownList(Direction::getStatusList(), ['class' => 'form-select']) ?>

            <div class="mb-4">
                <label class="form-label">Руководители направления</label>
                <div id="managers-container">
                    <input type="text" 
                           id="manager-search" 
                           class="form-control mb-2" 
                           placeholder="Начните вводить ФИО для поиска..."
                           autocomplete="off">
                    <div id="search-results" class="search-results-dropdown" style="display: none;"></div>
                    <div id="selected-managers" class="selected-managers">
                        <?php foreach ($selectedManagers as $manager): ?>
                            <div class="selected-manager" data-id="<?= $manager['id'] ?>">
                                <span class="selected-manager__text"><?= Html::encode($manager['text']) ?></span>
                                <button type="button" class="selected-manager__remove" onclick="removeManager('<?= $manager['id'] ?>')">
                                    <i class="fas fa-times"></i>
                                </button>
                                <input type="hidden" name="Direction[manager_ids_array][]" value="<?= $manager['id'] ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <small class="form-text text-muted">Введите минимум 2 символа для поиска</small>
            </div>
        </div>
    </div>

    <div class="form-group mt-4">
        <div class="d-flex gap-2">
            <?= Html::submitButton(
                $model->isNewRecord ? '<i class="fas fa-plus me-2"></i>Создать направление' : '<i class="fas fa-save me-2"></i>Сохранить',
                ['class' => 'nku-btn nku-btn--primary nku-btn--lg']
            ) ?>
            <?= Html::a(
                'Отмена',
                $model->isNewRecord ? ['index'] : ['view', 'id' => (string)$model->_id],
                ['class' => 'nku-btn nku-btn--secondary nku-btn--lg']
            ) ?>
        </div>
    </div>

    <?php ActiveForm::end(); ?>
</div>

<style>
.search-results-dropdown {
    position: absolute;
    z-index: 1000;
    width: calc(100% - 2rem);
    max-height: 250px;
    overflow-y: auto;
    background: white;
    border: 1px solid var(--nku-color-border);
    border-radius: 6px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.search-result-item {
    padding: 0.75rem 1rem;
    cursor: pointer;
    border-bottom: 1px solid var(--nku-color-border);
    transition: background 0.15s ease;
}

.search-result-item:last-child {
    border-bottom: none;
}

.search-result-item:hover {
    background: var(--nku-color-bg-secondary);
}

.search-result-item__name {
    font-weight: 500;
}

.search-result-item__role {
    font-size: 0.8rem;
    color: var(--nku-color-text-secondary);
}

.selected-managers {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-top: 0.5rem;
}

.selected-manager {
    display: inline-flex;
    align-items: center;
    padding: 0.35rem 0.75rem;
    background: var(--nku-color-primary-light);
    border: 1px solid var(--nku-color-primary);
    border-radius: 20px;
    font-size: 0.85rem;
}

.selected-manager__text {
    margin-right: 0.5rem;
}

.selected-manager__remove {
    background: none;
    border: none;
    padding: 0;
    cursor: pointer;
    color: var(--nku-color-primary);
    line-height: 1;
}

.selected-manager__remove:hover {
    color: var(--nku-color-danger);
}

#managers-container {
    position: relative;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var searchInput = document.getElementById('manager-search');
    var searchResults = document.getElementById('search-results');
    var selectedManagers = document.getElementById('selected-managers');
    var searchTimeout = null;

    // Получаем уже выбранные ID
    function getSelectedIds() {
        var ids = [];
        selectedManagers.querySelectorAll('input[type="hidden"]').forEach(function(input) {
            ids.push(input.value);
        });
        return ids;
    }

    searchInput.addEventListener('input', function() {
        var query = this.value.trim();
        
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }
        
        if (query.length < 2) {
            searchResults.style.display = 'none';
            return;
        }
        
        searchTimeout = setTimeout(function() {
            fetch('<?= \yii\helpers\Url::to(['search-managers']) ?>?q=' + encodeURIComponent(query))
                .then(function(response) {
                    return response.json();
                })
                .then(function(data) {
                    var selectedIds = getSelectedIds();
                    var html = '';
                    
                    if (data.results && data.results.length > 0) {
                        data.results.forEach(function(item) {
                            if (selectedIds.indexOf(item.id) === -1) {
                                html += '<div class="search-result-item" data-id="' + item.id + '" data-text="' + item.text.replace(/"/g, '&quot;') + '">';
                                html += '<div class="search-result-item__name">' + item.text + '</div>';
                                if (item.role) {
                                    html += '<div class="search-result-item__role">' + item.role + '</div>';
                                }
                                html += '</div>';
                            }
                        });
                    }
                    
                    if (html) {
                        searchResults.innerHTML = html;
                        searchResults.style.display = 'block';
                        
                        // Добавляем обработчики кликов
                        searchResults.querySelectorAll('.search-result-item').forEach(function(item) {
                            item.addEventListener('click', function() {
                                addManager(this.dataset.id, this.dataset.text);
                                searchInput.value = '';
                                searchResults.style.display = 'none';
                            });
                        });
                    } else {
                        searchResults.innerHTML = '<div class="search-result-item text-muted">Не найдено</div>';
                        searchResults.style.display = 'block';
                    }
                })
                .catch(function(error) {
                    console.error('Search error:', error);
                });
        }, 300);
    });

    // Закрытие результатов при клике вне
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.style.display = 'none';
        }
    });

    // Функция добавления руководителя
    window.addManager = function(id, text) {
        var html = '<div class="selected-manager" data-id="' + id + '">';
        html += '<span class="selected-manager__text">' + text + '</span>';
        html += '<button type="button" class="selected-manager__remove" onclick="removeManager(\'' + id + '\')">';
        html += '<i class="fas fa-times"></i>';
        html += '</button>';
        html += '<input type="hidden" name="Direction[manager_ids_array][]" value="' + id + '">';
        html += '</div>';
        
        selectedManagers.insertAdjacentHTML('beforeend', html);
    };

    // Функция удаления руководителя
    window.removeManager = function(id) {
        var element = selectedManagers.querySelector('.selected-manager[data-id="' + id + '"]');
        if (element) {
            element.remove();
        }
    };
});
</script>
