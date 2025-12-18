<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\Department;
use app\models\User;

/** @var yii\web\View $this */
/** @var app\models\Department $model */
/** @var app\models\User[] $availableUsers */
/** @var app\models\User[] $departmentUsers */

$this->title = $model->isMainDepartment() ? 'Управление пользователями подразделения' : 'Управление пользователями департамента';
$this->params['breadcrumbs'][] = ['label' => 'Админка', 'url' => ['/admin']];
$this->params['breadcrumbs'][] = ['label' => 'Подразделения', 'url' => ['index']];
if ($model->isSubdepartment() && $model->parent) {
    $this->params['breadcrumbs'][] = ['label' => $model->parent->name, 'url' => ['view', 'id' => (string)$model->parent_id]];
}
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => (string)$model->_id]];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="department-manage-users">

    <h1><?= Html::encode($this->title) ?></h1>
    <h3><?= $model->isMainDepartment() ? 'Подразделение' : 'Департамент' ?>: <?= Html::encode($model->name) ?></h3>
    <?php if ($model->isSubdepartment() && $model->parent): ?>
        <p class="text-muted">Родительское подразделение: <?= Html::encode($model->parent->name) ?></p>
    <?php endif; ?>

    <?php $form = ActiveForm::begin(); ?>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Пользователи в <?= $model->isMainDepartment() ? 'подразделении' : 'департаменте' ?></h5>
                    <small class="text-muted">Вы можете изменять роли пользователей прямо здесь</small>
                </div>
                <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                    <?php if (empty($departmentUsers)): ?>
                        <p class="text-muted">В <?= $model->isMainDepartment() ? 'подразделении' : 'департаменте' ?> нет пользователей.</p>
                    <?php else: ?>
                        <?php foreach ($departmentUsers as $user): ?>
                            <div class="mb-3 p-2 border rounded">
                                <div class="form-check mb-2">
                                    <input 
                                        class="form-check-input" 
                                        type="checkbox" 
                                        name="users[]" 
                                        value="<?= (string)$user->_id ?>" 
                                        id="user_<?= (string)$user->_id ?>"
                                        checked
                                    >
                                    <label class="form-check-label" for="user_<?= (string)$user->_id ?>">
                                        <?= Html::encode($user->fio) ?> 
                                        <small class="text-muted">(<?= Html::encode($user->email) ?>)</small>
                                    </label>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="badge badge-<?= [
                                            User::ROLE_ADMIN => 'danger',
                                            User::ROLE_RECTOR => 'warning',
                                            User::ROLE_TOP_MANAGER => 'primary',
                                            User::ROLE_MANAGER => 'info',
                                            User::ROLE_EXECUTOR => 'secondary',
                                        ][$user->role] ?? 'secondary' ?>" id="role_badge_<?= (string)$user->_id ?>">
                                            <?= [
                                                User::ROLE_ADMIN => 'Админ',
                                                User::ROLE_RECTOR => 'Руководитель',
                                                User::ROLE_TOP_MANAGER => 'Топ-менеджер',
                                                User::ROLE_MANAGER => 'Менеджер',
                                                User::ROLE_EXECUTOR => 'Исполнитель',
                                            ][$user->role] ?? $user->role ?>
                                        </span>
                                    </div>
                                    <div class="ml-2" style="min-width: 200px;">
                                        <select 
                                            class="form-control form-control-sm user-role-select" 
                                            data-user-id="<?= (string)$user->_id ?>"
                                            data-department-id="<?= (string)$model->_id ?>"
                                            <?= $user->role === User::ROLE_ADMIN ? 'disabled' : '' ?>
                                        >
                                            <?php if ($user->role === User::ROLE_ADMIN): ?>
                                                <option value="<?= User::ROLE_ADMIN ?>" selected>
                                                    Администратор (нельзя изменить)
                                                </option>
                                            <?php endif; ?>
                                            <option value="<?= User::ROLE_RECTOR ?>" <?= $user->role === User::ROLE_RECTOR ? 'selected' : '' ?>>
                                                Руководитель
                                            </option>
                                            <option value="<?= User::ROLE_TOP_MANAGER ?>" <?= $user->role === User::ROLE_TOP_MANAGER ? 'selected' : '' ?>>
                                                Топ-менеджер
                                            </option>
                                            <option value="<?= User::ROLE_MANAGER ?>" <?= $user->role === User::ROLE_MANAGER ? 'selected' : '' ?>>
                                                Менеджер
                                            </option>
                                            <option value="<?= User::ROLE_EXECUTOR ?>" <?= $user->role === User::ROLE_EXECUTOR ? 'selected' : '' ?>>
                                                Исполнитель
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Доступные пользователи</h5>
                </div>
                <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                    <?php if (empty($availableUsers)): ?>
                        <p class="text-muted">Нет доступных пользователей.</p>
                    <?php else: ?>
                        <?php foreach ($availableUsers as $user): ?>
                            <div class="form-check mb-2">
                                <input 
                                    class="form-check-input" 
                                    type="checkbox" 
                                    name="users[]" 
                                    value="<?= (string)$user->_id ?>" 
                                    id="user_<?= (string)$user->_id ?>"
                                >
                                <label class="form-check-label" for="user_<?= (string)$user->_id ?>">
                                    <?= Html::encode($user->fio) ?> 
                                    <small class="text-muted">(<?= Html::encode($user->email) ?>)</small>
                                    <?php if ($user->department_id && $model->isMainDepartment()): ?>
                                        <small class="text-warning">[<?= Html::encode($user->department->name) ?>]</small>
                                    <?php elseif ($user->subdepartment_id && $model->isSubdepartment()): ?>
                                        <small class="text-info">[департамент: <?= Html::encode($user->subdepartment->name) ?>]</small>
                                    <?php endif; ?>
                                    <span class="badge badge-<?= [
                                        User::ROLE_ADMIN => 'danger',
                                        User::ROLE_RECTOR => 'warning',
                                        User::ROLE_TOP_MANAGER => 'primary',
                                        User::ROLE_MANAGER => 'info',
                                        User::ROLE_EXECUTOR => 'secondary',
                                    ][$user->role] ?? 'secondary' ?>">
                                        <?= [
                                            User::ROLE_ADMIN => 'Админ',
                                            User::ROLE_RECTOR => 'Руководитель',
                                            User::ROLE_TOP_MANAGER => 'Топ-менеджер',
                                            User::ROLE_MANAGER => 'Менеджер',
                                            User::ROLE_EXECUTOR => 'Исполнитель',
                                        ][$user->role] ?? $user->role ?>
                                    </span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="form-group mt-3">
        <?= Html::submitButton('Сохранить изменения', ['class' => 'btn btn-success']) ?>
        <?= Html::a('Отмена', ['view', 'id' => (string)$model->_id], ['class' => 'btn btn-secondary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>

<style>
.department-manage-users .card {
    border: 1px solid #dee2e6;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.department-manage-users .card-header {
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
    font-weight: bold;
}

.department-manage-users .form-check-label {
    cursor: pointer;
    width: 100%;
}

.department-manage-users .form-check-input {
    cursor: pointer;
}

.department-manage-users .user-role-select {
    font-size: 0.875rem;
}
</style>

<?php
$this->registerJs("
$(document).on('change', '.user-role-select', function() {
    var select = $(this);
    var userId = select.data('user-id');
    var departmentId = select.data('department-id');
    var newRole = select.val();
    var badge = $('#role_badge_' + userId);
    
    // Блокируем select на время запроса
    select.prop('disabled', true);
    
    $.ajax({
        url: '" . \yii\helpers\Url::to(['department/update-user-role']) . "',
        method: 'POST',
        data: {
            user_id: userId,
            department_id: departmentId,
            role: newRole
        },
        success: function(response) {
            if (response.success) {
                // Обновляем badge
                var roleLabels = {
                    '" . User::ROLE_ADMIN . "': 'Админ',
                    '" . User::ROLE_RECTOR . "': 'Руководитель',
                    '" . User::ROLE_TOP_MANAGER . "': 'Топ-менеджер',
                    '" . User::ROLE_MANAGER . "': 'Менеджер',
                    '" . User::ROLE_EXECUTOR . "': 'Исполнитель'
                };
                var roleColors = {
                    '" . User::ROLE_ADMIN . "': 'danger',
                    '" . User::ROLE_RECTOR . "': 'warning',
                    '" . User::ROLE_TOP_MANAGER . "': 'primary',
                    '" . User::ROLE_MANAGER . "': 'info',
                    '" . User::ROLE_EXECUTOR . "': 'secondary'
                };
                
                badge.removeClass('badge-danger badge-warning badge-primary badge-info badge-secondary');
                badge.addClass('badge-' + roleColors[newRole]);
                badge.text(roleLabels[newRole]);
                
                // Показываем уведомление
                if (typeof toastr !== 'undefined') {
                    toastr.success('Роль успешно обновлена');
                } else {
                    alert('Роль успешно обновлена');
                }
            } else {
                // Возвращаем старое значение
                select.val(response.oldRole || '');
                if (typeof toastr !== 'undefined') {
                    toastr.error(response.message || 'Ошибка при обновлении роли');
                } else {
                    alert(response.message || 'Ошибка при обновлении роли');
                }
            }
        },
        error: function() {
            // Возвращаем старое значение
            var oldRole = badge.data('old-role') || select.find('option:selected').val();
            select.val(oldRole);
            if (typeof toastr !== 'undefined') {
                toastr.error('Ошибка при обновлении роли');
            } else {
                alert('Ошибка при обновлении роли');
            }
        },
        complete: function() {
            select.prop('disabled', false);
        }
    });
});
");
?>

