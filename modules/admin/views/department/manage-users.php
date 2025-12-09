<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\Department;
use app\models\User;

/** @var yii\web\View $this */
/** @var app\models\Department $model */
/** @var app\models\User[] $availableUsers */
/** @var app\models\User[] $departmentUsers */

$this->title = 'Управление пользователями подразделения';
$this->params['breadcrumbs'][] = ['label' => 'Админка', 'url' => ['/admin']];
$this->params['breadcrumbs'][] = ['label' => 'Подразделения', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => (string)$model->_id]];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="department-manage-users">

    <h1><?= Html::encode($this->title) ?></h1>
    <h3>Подразделение: <?= Html::encode($model->name) ?></h3>

    <?php $form = ActiveForm::begin(); ?>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Пользователи в подразделении</h5>
                </div>
                <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                    <?php if (empty($departmentUsers)): ?>
                        <p class="text-muted">В подразделении нет пользователей.</p>
                    <?php else: ?>
                        <?php foreach ($departmentUsers as $user): ?>
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
                                    <span class="badge badge-<?= [
                                        User::ROLE_ADMIN => 'danger',
                                        User::ROLE_RECTOR => 'warning',
                                        User::ROLE_MANAGER => 'info',
                                        User::ROLE_EXECUTOR => 'secondary',
                                    ][$user->role] ?? 'secondary' ?>">
                                        <?= [
                                            User::ROLE_ADMIN => 'Админ',
                                            User::ROLE_RECTOR => 'Ректор',
                                            User::ROLE_MANAGER => 'Руководитель',
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
                                    <?php if ($user->department_id): ?>
                                        <small class="text-warning">[<?= Html::encode($user->department->name) ?>]</small>
                                    <?php endif; ?>
                                    <span class="badge badge-<?= [
                                        User::ROLE_ADMIN => 'danger',
                                        User::ROLE_RECTOR => 'warning',
                                        User::ROLE_MANAGER => 'info',
                                        User::ROLE_EXECUTOR => 'secondary',
                                    ][$user->role] ?? 'secondary' ?>">
                                        <?= [
                                            User::ROLE_ADMIN => 'Админ',
                                            User::ROLE_RECTOR => 'Ректор',
                                            User::ROLE_MANAGER => 'Руководитель',
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
</style>

