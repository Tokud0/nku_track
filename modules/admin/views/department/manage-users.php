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

$roleLabels = [
    User::ROLE_ADMIN => 'Админ',
    User::ROLE_HEAD => 'Руководитель',
    User::ROLE_RECTOR => 'Ректор',
    User::ROLE_TOP_MANAGER => 'Топ-менеджер',
    User::ROLE_MANAGER => 'Менеджер',
    User::ROLE_EXECUTOR => 'Исполнитель',
];
$roleColors = [
    User::ROLE_ADMIN => 'danger',
    User::ROLE_HEAD => 'warning',
    User::ROLE_RECTOR => 'info',
    User::ROLE_TOP_MANAGER => 'primary',
    User::ROLE_MANAGER => 'info',
    User::ROLE_EXECUTOR => 'secondary',
];

// Исключаем уже привязанных к этому подразделению из правой колонки и сортируем
// «свободные» (без подразделения) — сверху, потом «из других подразделений»
$departmentUserIds = [];
foreach ($departmentUsers as $u) {
    $departmentUserIds[(string)$u->_id] = true;
}
$availableSorted = [];
foreach ($availableUsers as $u) {
    if (isset($departmentUserIds[(string)$u->_id])) {
        continue;
    }
    if ($model->isMainDepartment()) {
        $isFree = empty($u->department_id);
    } else {
        // в контексте субдепартамента «свободные» = в нужном родительском подразделении, без субдепартамента
        $isFree = empty($u->subdepartment_id);
    }
    $availableSorted[] = ['user' => $u, 'isFree' => $isFree];
}
usort($availableSorted, function ($a, $b) {
    if ($a['isFree'] !== $b['isFree']) {
        return $a['isFree'] ? -1 : 1;
    }
    return strcasecmp((string)($a['user']->fio ?? ''), (string)($b['user']->fio ?? ''));
});

$currentLabel = $model->isMainDepartment() ? 'подразделении' : 'департаменте';
?>

<div class="department-manage-users">

    <h1><?= Html::encode($this->title) ?></h1>
    <h3><?= $model->isMainDepartment() ? 'Подразделение' : 'Департамент' ?>: <?= Html::encode($model->name) ?></h3>
    <?php if ($model->isSubdepartment() && $model->parent): ?>
        <p class="text-muted">Родительское подразделение: <?= Html::encode($model->parent->name) ?></p>
    <?php endif; ?>

    <?php $form = ActiveForm::begin(['id' => 'manage-users-form']); ?>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="mb-0">Пользователи в <?= $currentLabel ?></h5>
                        <span class="badge badge-secondary mu-count" data-side="current">
                            <span class="mu-count-shown"><?= count($departmentUsers) ?></span> /
                            <span class="mu-count-total"><?= count($departmentUsers) ?></span>
                        </span>
                    </div>
                    <small class="text-muted d-block mb-2">Снимите галочку, чтобы открепить. Роль меняется тут же.</small>
                    <input type="text" class="form-control form-control-sm mu-search"
                           data-side="current"
                           placeholder="Поиск по ФИО или email...">
                </div>
                <div class="card-body mu-list" data-side="current" style="max-height: 480px; overflow-y: auto;">
                    <?php if (empty($departmentUsers)): ?>
                        <p class="text-muted mu-empty-msg">В <?= $currentLabel ?> нет пользователей.</p>
                    <?php else: ?>
                        <?php foreach ($departmentUsers as $u): ?>
                            <?php
                            $uid = (string)$u->_id;
                            $fio = (string)($u->fio ?? '');
                            $email = (string)($u->email ?? '');
                            $roleColor = $roleColors[$u->role] ?? 'secondary';
                            $roleLabel = $roleLabels[$u->role] ?? $u->role;
                            ?>
                            <div class="mb-3 p-2 border rounded mu-row"
                                 data-fio="<?= Html::encode(mb_strtolower($fio)) ?>"
                                 data-email="<?= Html::encode(mb_strtolower($email)) ?>"
                                 data-status="current">
                                <div class="form-check mb-2">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        name="users[]"
                                        value="<?= $uid ?>"
                                        id="cur_<?= $uid ?>"
                                        checked
                                    >
                                    <label class="form-check-label" for="cur_<?= $uid ?>">
                                        <?= Html::encode($fio) ?>
                                        <small class="text-muted">(<?= Html::encode($email) ?>)</small>
                                    </label>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="badge badge-<?= $roleColor ?>" id="role_badge_<?= $uid ?>">
                                            <?= $roleLabel ?>
                                        </span>
                                    </div>
                                    <div class="ml-2" style="min-width: 200px;">
                                        <select
                                            class="form-control form-control-sm user-role-select"
                                            data-user-id="<?= $uid ?>"
                                            data-department-id="<?= (string)$model->_id ?>"
                                            <?= $u->role === User::ROLE_ADMIN ? 'disabled' : '' ?>
                                        >
                                            <?php if ($u->role === User::ROLE_ADMIN): ?>
                                                <option value="<?= User::ROLE_ADMIN ?>" selected>
                                                    Администратор (нельзя изменить)
                                                </option>
                                            <?php endif; ?>
                                            <option value="<?= User::ROLE_HEAD ?>" <?= $u->role === User::ROLE_HEAD ? 'selected' : '' ?>>Руководитель</option>
                                            <option value="<?= User::ROLE_RECTOR ?>" <?= $u->role === User::ROLE_RECTOR ? 'selected' : '' ?>>Ректор</option>
                                            <option value="<?= User::ROLE_TOP_MANAGER ?>" <?= $u->role === User::ROLE_TOP_MANAGER ? 'selected' : '' ?>>Топ-менеджер</option>
                                            <option value="<?= User::ROLE_MANAGER ?>" <?= $u->role === User::ROLE_MANAGER ? 'selected' : '' ?>>Менеджер</option>
                                            <option value="<?= User::ROLE_EXECUTOR ?>" <?= $u->role === User::ROLE_EXECUTOR ? 'selected' : '' ?>>Исполнитель</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <p class="text-muted mu-no-results" style="display: none;">Ничего не найдено.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="mb-0">Доступные пользователи</h5>
                        <span class="badge badge-secondary mu-count" data-side="available">
                            <span class="mu-count-shown"><?= count($availableSorted) ?></span> /
                            <span class="mu-count-total"><?= count($availableSorted) ?></span>
                        </span>
                    </div>
                    <small class="text-muted d-block mb-2">Свободные пользователи показаны сверху.</small>
                    <input type="text" class="form-control form-control-sm mu-search mb-2"
                           data-side="available"
                           placeholder="Поиск по ФИО или email...">
                    <div class="btn-group btn-group-sm mu-filter-group" role="group" data-side="available">
                        <button type="button" class="btn btn-outline-primary active mu-filter-btn" data-filter="all">Все</button>
                        <button type="button" class="btn btn-outline-primary mu-filter-btn" data-filter="free">Без подразделения</button>
                        <button type="button" class="btn btn-outline-primary mu-filter-btn" data-filter="other">Из других подразделений</button>
                    </div>
                </div>
                <div class="card-body mu-list" data-side="available" style="max-height: 480px; overflow-y: auto;">
                    <?php if (empty($availableSorted)): ?>
                        <p class="text-muted mu-empty-msg">Нет доступных пользователей.</p>
                    <?php else: ?>
                        <?php foreach ($availableSorted as $row): ?>
                            <?php
                            $u = $row['user'];
                            $uid = (string)$u->_id;
                            $fio = (string)($u->fio ?? '');
                            $email = (string)($u->email ?? '');
                            $status = $row['isFree'] ? 'free' : 'other';
                            $roleColor = $roleColors[$u->role] ?? 'secondary';
                            $roleLabel = $roleLabels[$u->role] ?? $u->role;
                            ?>
                            <div class="form-check mb-2 mu-row"
                                 data-fio="<?= Html::encode(mb_strtolower($fio)) ?>"
                                 data-email="<?= Html::encode(mb_strtolower($email)) ?>"
                                 data-status="<?= $status ?>">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="users[]"
                                    value="<?= $uid ?>"
                                    id="avail_<?= $uid ?>"
                                >
                                <label class="form-check-label" for="avail_<?= $uid ?>">
                                    <?= Html::encode($fio) ?>
                                    <small class="text-muted">(<?= Html::encode($email) ?>)</small>
                                    <?php if ($status === 'free'): ?>
                                        <span class="badge badge-success ms-1">Без подразделения</span>
                                    <?php else: ?>
                                        <?php if ($model->isMainDepartment() && $u->department_id && $u->department): ?>
                                            <span class="badge badge-warning ms-1">[<?= Html::encode($u->department->name) ?>]</span>
                                        <?php elseif ($model->isSubdepartment() && $u->subdepartment_id && $u->subdepartment): ?>
                                            <span class="badge badge-info ms-1">[департамент: <?= Html::encode($u->subdepartment->name) ?>]</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <span class="badge badge-<?= $roleColor ?> ms-1"><?= $roleLabel ?></span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                        <p class="text-muted mu-no-results" style="display: none;">Ничего не найдено.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="form-group mt-3 d-flex align-items-center gap-3 flex-wrap">
        <?= Html::submitButton('Сохранить изменения', ['class' => 'btn btn-success']) ?>
        <?= Html::a('Отмена', ['view', 'id' => (string)$model->_id], ['class' => 'btn btn-secondary']) ?>
        <span class="text-muted">Выбрано: <strong id="mu-selected-total">0</strong></span>
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
.department-manage-users .mu-search {
    margin-top: 4px;
}
.department-manage-users .mu-filter-btn.active {
    background-color: #0d6efd;
    color: #fff;
}
</style>

<?php
$updateRoleUrl = \yii\helpers\Url::to(['department/update-user-role']);
$jsRoleLabels = json_encode($roleLabels, JSON_UNESCAPED_UNICODE);
$jsRoleColors = json_encode($roleColors);

$this->registerJs(<<<JS
(function() {
    var roleLabels = $jsRoleLabels;
    var roleColors = $jsRoleColors;

    function applyFilter(side) {
        var listSel = '.mu-list[data-side="' + side + '"]';
        var \$list = jQuery(listSel);
        if (!\$list.length) return;
        var query = (jQuery('.mu-search[data-side="' + side + '"]').val() || '').toLowerCase().trim();
        var statusFilter = 'all';
        var \$activeBtn = jQuery('.mu-filter-group[data-side="' + side + '"] .mu-filter-btn.active');
        if (\$activeBtn.length) statusFilter = \$activeBtn.data('filter');
        var shown = 0;
        var total = 0;
        \$list.find('.mu-row').each(function() {
            total++;
            var \$row = jQuery(this);
            var fio = (\$row.data('fio') || '').toString();
            var email = (\$row.data('email') || '').toString();
            var status = (\$row.data('status') || '').toString();
            var matchQuery = !query || fio.indexOf(query) !== -1 || email.indexOf(query) !== -1;
            var matchStatus = statusFilter === 'all' || status === statusFilter;
            if (matchQuery && matchStatus) {
                \$row.show();
                shown++;
            } else {
                \$row.hide();
            }
        });
        var \$count = jQuery('.mu-count[data-side="' + side + '"]');
        \$count.find('.mu-count-shown').text(shown);
        \$count.find('.mu-count-total').text(total);
        \$list.find('.mu-no-results').toggle(shown === 0 && total > 0);
        \$list.find('.mu-empty-msg').toggle(total === 0);
    }

    function recountSelected() {
        var n = jQuery('#manage-users-form input[name="users[]"]:checked').length;
        jQuery('#mu-selected-total').text(n);
    }

    jQuery(document).on('input', '.mu-search', function() {
        applyFilter(jQuery(this).data('side'));
    });

    jQuery(document).on('click', '.mu-filter-btn', function() {
        var \$btn = jQuery(this);
        \$btn.closest('.mu-filter-group').find('.mu-filter-btn').removeClass('active');
        \$btn.addClass('active');
        applyFilter(\$btn.closest('.mu-filter-group').data('side'));
    });

    jQuery(document).on('change', '#manage-users-form input[name="users[]"]', recountSelected);

    jQuery(document).on('change', '.user-role-select', function() {
        var \$select = jQuery(this);
        var userId = \$select.data('user-id');
        var departmentId = \$select.data('department-id');
        var newRole = \$select.val();
        var \$badge = jQuery('#role_badge_' + userId);
        \$select.prop('disabled', true);
        jQuery.ajax({
            url: '$updateRoleUrl',
            method: 'POST',
            data: { user_id: userId, department_id: departmentId, role: newRole },
            success: function(response) {
                if (response.success) {
                    \$badge.removeClass(function(i, cls) {
                        return (cls.match(/(^|\s)badge-\S+/g) || []).join(' ');
                    });
                    \$badge.addClass('badge badge-' + (roleColors[newRole] || 'secondary'));
                    \$badge.text(roleLabels[newRole] || newRole);
                    if (typeof toastr !== 'undefined') { toastr.success('Роль успешно обновлена'); }
                    else { /* silent */ }
                } else {
                    \$select.val(response.oldRole || '');
                    if (typeof toastr !== 'undefined') { toastr.error(response.message || 'Ошибка при обновлении роли'); }
                    else { alert(response.message || 'Ошибка при обновлении роли'); }
                }
            },
            error: function() {
                if (typeof toastr !== 'undefined') { toastr.error('Ошибка при обновлении роли'); }
                else { alert('Ошибка при обновлении роли'); }
            },
            complete: function() { \$select.prop('disabled', false); }
        });
    });

    applyFilter('current');
    applyFilter('available');
    recountSelected();
})();
JS
);
?>
