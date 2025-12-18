<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\Task;
use app\models\Project;
use app\models\Department;
use app\models\User;

/** @var yii\web\View $this */
/** @var app\models\Task $model */
/** @var app\models\Project $project */

$this->title = 'Редактирование задачи';
$this->params['breadcrumbs'][] = ['label' => 'Проекты', 'url' => ['project/index']];
$this->params['breadcrumbs'][] = ['label' => $project->title, 'url' => ['project/view', 'id' => (string)$project->_id]];
$this->params['breadcrumbs'][] = $this->title;

$user = Yii::$app->user->identity;
// Исполнитель может редактировать только свои задачи
$isExecutor = $user->role === User::ROLE_EXECUTOR && $model->isAssignedToUser($user);
// Менеджер, топ-менеджер, ректор и админ имеют полный доступ к редактированию
$canEditAll = in_array($user->role, [User::ROLE_MANAGER, User::ROLE_TOP_MANAGER, User::ROLE_RECTOR, User::ROLE_ADMIN]);

// Определяем текущий тип назначения
$currentExecutorType = 'none';
$currentExecutorId = null;
if ($model->executor_user_from_department_id) {
    $currentExecutorType = 'user_from_department';
    $currentExecutorId = (string)$model->executor_user_from_department_id;
} elseif ($model->executor_subdepartment_id) {
    $currentExecutorType = 'subdepartment';
    $currentExecutorId = (string)$model->executor_subdepartment_id;
} elseif ($model->executor_user_from_subdepartment_id) {
    $currentExecutorType = 'user_from_subdepartment';
    $currentExecutorId = (string)$model->executor_user_from_subdepartment_id;
}

// Получаем подразделение проекта
$department = $project->department;
?>

<div class="task-update">

    <h1><?= Html::encode($this->title) ?></h1>
    <h3>Проект: <?= Html::encode($project->title) ?></h3>

    <div class="task-form">

        <?php $form = ActiveForm::begin(); ?>

        <?php if (!$isExecutor && $canEditAll): ?>
            <?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>
        <?php else: ?>
            <div class="form-group">
                <label class="control-label">Название</label>
                <div><?= Html::encode($model->title) ?></div>
            </div>
        <?php endif; ?>

        <?= $form->field($model, 'description')->textarea(['rows' => 6]) ?>

        <div class="row">
            <div class="col-md-6">
                <?= $form->field($model, 'status')->dropDownList([
                    Task::STATUS_TODO => 'К выполнению',
                    Task::STATUS_IN_PROGRESS => 'В работе',
                    Task::STATUS_REVIEW => 'На проверке',
                    Task::STATUS_DONE => 'Выполнено',
                    Task::STATUS_CANCELED => 'Отменено',
                ]) ?>
            </div>
            <div class="col-md-6">
                <?php if (!$isExecutor && $canEditAll): ?>
                    <?= $form->field($model, 'priority')->dropDownList([
                        Task::PRIORITY_LOW => 'Низкий',
                        Task::PRIORITY_MEDIUM => 'Средний',
                        Task::PRIORITY_HIGH => 'Высокий',
                        Task::PRIORITY_CRITICAL => 'Критический',
                    ]) ?>
                <?php else: ?>
                    <div class="form-group">
                        <label class="control-label">Приоритет</label>
                        <div>
                            <span class="badge badge-<?= $model->getPriorityBadgeColor() ?>">
                                <?= $model->getPriorityLabel() ?>
                            </span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!$isExecutor && $department): ?>
            <h4>Назначение исполнителя</h4>
            <div class="alert alert-info">
                Подразделение: <strong><?= Html::encode($department->name) ?></strong>
            </div>

            <div class="form-group">
                <label class="control-label">Тип назначения</label>
                <div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="executor_type" id="executor_type_user_department" value="user_from_department" <?= $currentExecutorType === 'user_from_department' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="executor_type_user_department">
                            Один человек из подразделения
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="executor_type" id="executor_type_subdepartment" value="subdepartment" <?= $currentExecutorType === 'subdepartment' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="executor_type_subdepartment">
                            Весь департамент
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="executor_type" id="executor_type_user_subdepartment" value="user_from_subdepartment" <?= $currentExecutorType === 'user_from_subdepartment' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="executor_type_user_subdepartment">
                            Один человек из департамента
                        </label>
                    </div>
                </div>
            </div>

            <?php
            // Пользователи из подразделения
            $usersFromDepartment = User::find()
                ->where(['department_id' => $department->_id])
                ->andWhere(['subdepartment_id' => null])
                ->orderBy(['fio' => SORT_ASC])
                ->all();
            $usersFromDepartmentList = [];
            foreach ($usersFromDepartment as $userItem) {
                $usersFromDepartmentList[(string)$userItem->_id] = $userItem->fio . ' (' . $userItem->email . ')';
            }

            // Департаменты подразделения
            $subdepartments = Department::find()
                ->where(['parent_id' => $department->_id])
                ->orderBy(['name' => SORT_ASC])
                ->all();
            $subdepartmentsList = [];
            foreach ($subdepartments as $subdept) {
                $subdepartmentsList[(string)$subdept->_id] = $subdept->name;
            }

            // Для третьего варианта нужно подготовить данные для JavaScript
            $usersBySubdepartment = [];
            foreach ($subdepartments as $subdept) {
                $usersInSubdept = User::find()
                    ->where(['subdepartment_id' => $subdept->_id])
                    ->orderBy(['fio' => SORT_ASC])
                    ->all();
                $usersBySubdepartment[(string)$subdept->_id] = [];
                foreach ($usersInSubdept as $user) {
                    $usersBySubdepartment[(string)$subdept->_id][(string)$user->_id] = $user->fio . ' (' . $user->email . ')';
                }
            }
            $usersBySubdepartmentJson = json_encode($usersBySubdepartment);
            
            // Определяем выбранный департамент для текущего пользователя из департамента
            $selectedSubdepartmentForUser = null;
            if ($currentExecutorType === 'user_from_subdepartment' && $model->executor_user_from_subdepartment_id) {
                $currentUser = User::findOne(['_id' => $model->executor_user_from_subdepartment_id]);
                if ($currentUser && $currentUser->subdepartment_id) {
                    $selectedSubdepartmentForUser = (string)$currentUser->subdepartment_id;
                }
            }
            ?>

            <div id="executor_user_from_department_field" style="display: <?= $currentExecutorType === 'user_from_department' ? 'block' : 'none' ?>;">
                <?= $form->field($model, 'executor_user_from_department_id')->dropDownList(
                    $usersFromDepartmentList,
                    ['prompt' => 'Выберите пользователя из подразделения']
                ) ?>
            </div>

            <div id="executor_subdepartment_field" style="display: <?= $currentExecutorType === 'subdepartment' ? 'block' : 'none' ?>;">
                <?= $form->field($model, 'executor_subdepartment_id')->dropDownList(
                    $subdepartmentsList,
                    ['prompt' => 'Выберите департамент']
                ) ?>
            </div>

            <div id="executor_user_from_subdepartment_field" style="display: <?= $currentExecutorType === 'user_from_subdepartment' ? 'block' : 'none' ?>;">
                <div id="subdepartment_select_container">
                    <?= Html::label('Департамент', 'subdepartment_select') ?>
                    <?= Html::dropDownList(
                        'subdepartment_select',
                        $selectedSubdepartmentForUser,
                        $subdepartmentsList,
                        [
                            'id' => 'subdepartment_select',
                            'class' => 'form-control',
                            'prompt' => 'Сначала выберите департамент'
                        ]
                    ) ?>
                </div>
                <div id="user_from_subdepartment_select_container" style="margin-top: 15px; display: <?= $selectedSubdepartmentForUser ? 'block' : 'none' ?>;">
                    <?php
                    $currentUsersFromSubdeptList = [];
                    if ($selectedSubdepartmentForUser && isset($usersBySubdepartment[$selectedSubdepartmentForUser])) {
                        $currentUsersFromSubdeptList = $usersBySubdepartment[$selectedSubdepartmentForUser];
                    }
                    ?>
                    <?= $form->field($model, 'executor_user_from_subdepartment_id')->dropDownList(
                        $currentUsersFromSubdeptList,
                        ['prompt' => 'Выберите пользователя из выбранного департамента', 'id' => 'task-executor_user_from_subdepartment_id']
                    )->label('Пользователь из департамента') ?>
                </div>
            </div>

            <?php
            $this->registerJs("
            var usersBySubdepartment = {$usersBySubdepartmentJson};
            var currentSubdepartmentId = '{$selectedSubdepartmentForUser}';
            
            $('input[name=\"executor_type\"]').on('change', function() {
                var type = $(this).val();
                $('#executor_user_from_department_field').hide();
                $('#executor_subdepartment_field').hide();
                $('#executor_user_from_subdepartment_field').hide();
                $('#task-executor_user_from_department_id').val('');
                $('#task-executor_subdepartment_id').val('');
                $('#task-executor_user_from_subdepartment_id').val('');
                $('#subdepartment_select').val('');
                $('#user_from_subdepartment_select_container').hide();
                
                if (type === 'user_from_department') {
                    $('#executor_user_from_department_field').show();
                } else if (type === 'subdepartment') {
                    $('#executor_subdepartment_field').show();
                } else if (type === 'user_from_subdepartment') {
                    $('#executor_user_from_subdepartment_field').show();
                    if (currentSubdepartmentId) {
                        $('#subdepartment_select').val(currentSubdepartmentId).trigger('change');
                    }
                }
            });
            
            $('#subdepartment_select').on('change', function() {
                var subdepartmentId = $(this).val();
                var userSelect = $('#task-executor_user_from_subdepartment_id');
                userSelect.empty();
                userSelect.append('<option value=\"\">Выберите пользователя из выбранного департамента</option>');
                
                if (subdepartmentId && usersBySubdepartment[subdepartmentId]) {
                    $.each(usersBySubdepartment[subdepartmentId], function(userId, userName) {
                        userSelect.append('<option value=\"' + userId + '\">' + userName + '</option>');
                    });
                    $('#user_from_subdepartment_select_container').show();
                } else {
                    $('#user_from_subdepartment_select_container').hide();
                }
            });
            
            // Инициализация при загрузке
            if (currentSubdepartmentId) {
                $('#subdepartment_select').trigger('change');
            }
            ");
            ?>
        <?php elseif ($isExecutor): ?>
            <div class="form-group">
                <label class="control-label">Исполнитель</label>
                <div>
                    <?php
                    $assignedUsers = $model->getAssignedUsers();
                    if (!empty($assignedUsers)) {
                        $names = [];
                        foreach ($assignedUsers as $assignedUser) {
                            $names[] = Html::encode($assignedUser->fio);
                        }
                        echo implode(', ', $names);
                    } else {
                        echo '<span class="text-muted">Не назначен</span>';
                    }
                    ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6">
                <?= $form->field($model, 'start_date')->input('date', [
                    'value' => $model->start_date instanceof \MongoDB\BSON\UTCDateTime 
                        ? date('Y-m-d', $model->start_date->toDateTime()->getTimestamp()) 
                        : ''
                ]) ?>
            </div>
            <div class="col-md-6">
                <?= $form->field($model, 'due_date')->input('date', [
                    'value' => $model->due_date instanceof \MongoDB\BSON\UTCDateTime 
                        ? date('Y-m-d', $model->due_date->toDateTime()->getTimestamp()) 
                        : ''
                ]) ?>
            </div>
        </div>

        <?= $form->field($model, 'progress')->textInput(['type' => 'number', 'min' => 0, 'max' => 100]) ?>

        <div class="form-group">
            <?= Html::submitButton('Сохранить', ['class' => 'btn btn-primary']) ?>
            <?php if (!$isExecutor && $canEditAll): ?>
                <?= Html::a('Удалить', ['task/delete', 'id' => (string)$model->_id], [
                    'class' => 'btn btn-danger',
                    'data' => [
                        'confirm' => 'Вы уверены, что хотите удалить эту задачу?',
                        'method' => 'post',
                    ],
                ]) ?>
            <?php endif; ?>
            <?= Html::a('Отмена', ['project/view', 'id' => (string)$project->_id], ['class' => 'btn btn-secondary']) ?>
        </div>

        <?php ActiveForm::end(); ?>

    </div>

</div>
