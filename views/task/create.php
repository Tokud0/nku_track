<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\Task;
use app\models\Project;
use app\models\Department;
use app\models\User;
use app\models\GlobalProjectRole;

/** @var yii\web\View $this */
/** @var app\models\Task $model */
/** @var app\models\Project $project */
/** @var bool $isGlobalProject */

$this->title = 'Создание задачи';
$this->params['breadcrumbs'][] = ['label' => 'Проекты', 'url' => ['project/index']];
$this->params['breadcrumbs'][] = ['label' => $project->title, 'url' => ['project/view', 'id' => (string)$project->_id]];
$this->params['breadcrumbs'][] = $this->title;

// Получаем подразделение проекта
$department = $project->department;

// Убеждаемся, что переменная isGlobalProject определена
if (!isset($isGlobalProject)) {
    $isGlobalProject = $project->isGlobal();
}
?>

<div class="task-create">

    <h1><?= Html::encode($this->title) ?></h1>
    <h3>Проект: <?= Html::encode($project->title) ?></h3>

    <div class="task-form">

        <?php if (Yii::$app->session->hasFlash('error')): ?>
            <div class="alert alert-danger">
                <?= Yii::$app->session->getFlash('error') ?>
            </div>
        <?php endif; ?>

        <?php if (!$isGlobalProject && !$department): ?>
            <div class="alert alert-danger">
                У проекта не указано подразделение. Невозможно создать задачу.
            </div>
        <?php else: ?>
            <?php $form = ActiveForm::begin(); ?>

            <?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>

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
                    <?= $form->field($model, 'priority')->dropDownList([
                        Task::PRIORITY_LOW => 'Низкий',
                        Task::PRIORITY_MEDIUM => 'Средний',
                        Task::PRIORITY_HIGH => 'Высокий',
                        Task::PRIORITY_CRITICAL => 'Критический',
                    ]) ?>
                </div>
            </div>

            <h4>Назначение исполнителя</h4>
            <?php if (!$isGlobalProject && $department): ?>
                <div class="alert alert-info">
                    Подразделение: <strong><?= Html::encode($department->name) ?></strong>
                </div>
            <?php elseif ($isGlobalProject): ?>
                <div class="alert alert-info">
                    <strong>Глобальный проект</strong> - можно назначить любого пользователя с ролью в глобальном проекте
                </div>
            <?php endif; ?>

            <?php if ($isGlobalProject): ?>
                <?php
                // Для глобального проекта получаем всех пользователей с ролями в глобальном проекте
                $globalRoles = GlobalProjectRole::find()->all();
                $globalUserIds = [];
                foreach ($globalRoles as $role) {
                    if ($role->user_id) {
                        $globalUserIds[] = $role->user_id;
                    }
                }
                
                $allUsers = [];
                if (!empty($globalUserIds)) {
                    $users = User::find()
                        ->where(['_id' => ['$in' => $globalUserIds]])
                        ->orderBy(['fio' => SORT_ASC])
                        ->all();
                    foreach ($users as $user) {
                        $allUsers[(string)$user->_id] = $user->fio . ' (' . $user->email . ')';
                    }
                }
                // Инициализируем переменную для JavaScript (не используется для глобального проекта, но нужна для избежания ошибок)
                $usersBySubdepartmentJson = '{}';
                ?>
                <div class="form-group">
                    <?= $form->field($model, 'executor_user_from_department_id')->dropDownList(
                        $allUsers,
                        ['prompt' => 'Выберите пользователя']
                    )->label('Исполнитель') ?>
                </div>
            <?php else: ?>
                <div class="form-group">
                    <label class="control-label">Тип назначения</label>
                    <div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="executor_type" id="executor_type_user_department" value="user_from_department" checked>
                            <label class="form-check-label" for="executor_type_user_department">
                                Один человек из подразделения
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="executor_type" id="executor_type_subdepartment" value="subdepartment">
                            <label class="form-check-label" for="executor_type_subdepartment">
                                Весь департамент
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="executor_type" id="executor_type_user_subdepartment" value="user_from_subdepartment">
                            <label class="form-check-label" for="executor_type_user_subdepartment">
                                Один человек из департамента
                            </label>
                        </div>
                    </div>
                </div>

                <?php
                // Пользователи из подразделения (напрямую привязанные, без департамента)
                $usersFromDepartment = [];
                $usersFromDepartmentList = [];
                $subdepartmentsList = [];
                $usersBySubdepartment = [];
                
                if ($department) {
                    $usersFromDepartment = User::find()
                        ->where(['department_id' => $department->_id])
                        ->andWhere(['subdepartment_id' => null])
                        ->orderBy(['fio' => SORT_ASC])
                        ->all();
                    foreach ($usersFromDepartment as $user) {
                        $usersFromDepartmentList[(string)$user->_id] = $user->fio . ' (' . $user->email . ')';
                    }

                    // Департаменты подразделения
                    $subdepartments = Department::find()
                        ->where(['parent_id' => $department->_id])
                        ->orderBy(['name' => SORT_ASC])
                        ->all();
                    foreach ($subdepartments as $subdept) {
                        $subdepartmentsList[(string)$subdept->_id] = $subdept->name;
                    }

                    // Для третьего варианта нужно подготовить данные для JavaScript
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
                }
                $usersBySubdepartmentJson = json_encode($usersBySubdepartment);
                ?>

            <div id="executor_user_from_department_field">
                <?= $form->field($model, 'executor_user_from_department_id')->dropDownList(
                    $usersFromDepartmentList,
                    ['prompt' => 'Выберите пользователя из подразделения']
                ) ?>
            </div>

            <div id="executor_subdepartment_field" style="display: none;">
                <?= $form->field($model, 'executor_subdepartment_id')->dropDownList(
                    $subdepartmentsList,
                    ['prompt' => 'Выберите департамент']
                ) ?>
            </div>

            <div id="executor_user_from_subdepartment_field" style="display: none;">
                <div id="subdepartment_select_container">
                    <?= Html::label('Департамент', 'subdepartment_select') ?>
                    <?= Html::dropDownList(
                        'subdepartment_select',
                        null,
                        $subdepartmentsList,
                        [
                            'id' => 'subdepartment_select',
                            'class' => 'form-control',
                            'prompt' => 'Сначала выберите департамент'
                        ]
                    ) ?>
                </div>
                <div id="user_from_subdepartment_select_container" style="margin-top: 15px; display: none;">
                    <?= $form->field($model, 'executor_user_from_subdepartment_id')->dropDownList(
                        [],
                        ['prompt' => 'Выберите пользователя из выбранного департамента', 'id' => 'task-executor_user_from_subdepartment_id']
                    )->label('Пользователь из департамента') ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model, 'start_date')->input('date') ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($model, 'due_date')->input('date') ?>
                </div>
            </div>

            <h4>Подзадачи (To-Do лист)</h4>
            <div class="alert alert-info">
                <small>Добавьте этапы выполнения задачи. Прогресс будет рассчитываться автоматически на основе выполненных подзадач.</small>
            </div>
            <div id="subtasks-container">
                <div class="subtask-item mb-2">
                    <div class="input-group">
                        <input type="text" name="subtasks[]" class="form-control" placeholder="Например: 1. Первый этап">
                        <input type="hidden" name="subtask_completed[]" value="0">
                        <button type="button" class="btn btn-danger remove-subtask" style="display: none;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
            <button type="button" class="btn btn-success btn-sm mt-2" id="add-subtask">
                <i class="fas fa-plus me-1"></i>Добавить подзадачу
            </button>

            <div class="form-group mt-3" style="display: none;">
                <?= $form->field($model, 'progress')->hiddenInput()->label(false) ?>
            </div>

            <div class="form-group">
                <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
                <?php if ($isGlobalProject): ?>
                    <?= Html::a('Отмена', ['global-project/view', 'id' => (string)$project->_id], ['class' => 'btn btn-secondary']) ?>
                <?php else: ?>
                    <?= Html::a('Отмена', ['project/view', 'id' => (string)$project->_id], ['class' => 'btn btn-secondary']) ?>
                <?php endif; ?>
            </div>

            <?php ActiveForm::end(); ?>

            <?php
            // JavaScript только для обычных проектов (не глобальных)
            if (!$isGlobalProject):
                $this->registerJs("
                // Управление подзадачами
                var subtaskIndex = 1;
                
                $('#add-subtask').on('click', function() {
                    var subtaskHtml = '<div class=\"subtask-item mb-2\">' +
                        '<div class=\"input-group\">' +
                        '<input type=\"text\" name=\"subtasks[]\" class=\"form-control\" placeholder=\"Например: ' + (subtaskIndex + 1) + '. Следующий этап\">' +
                        '<input type=\"hidden\" name=\"subtask_completed[]\" value=\"0\">' +
                        '<button type=\"button\" class=\"btn btn-danger remove-subtask\">' +
                        '<i class=\"fas fa-times\"></i>' +
                        '</button>' +
                        '</div>' +
                        '</div>';
                    $('#subtasks-container').append(subtaskHtml);
                    subtaskIndex++;
                    updateRemoveButtons();
                });
                
                $(document).on('click', '.remove-subtask', function() {
                    $(this).closest('.subtask-item').remove();
                    updateRemoveButtons();
                });
                
                function updateRemoveButtons() {
                    var items = $('#subtasks-container .subtask-item');
                    if (items.length > 1) {
                        items.find('.remove-subtask').show();
                    } else {
                        items.find('.remove-subtask').hide();
                    }
                }
                
                updateRemoveButtons();
                
                var usersBySubdepartment = {$usersBySubdepartmentJson};
                
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
                ");
            else:
                // JavaScript для глобального проекта (только управление подзадачами)
                $this->registerJs("
                // Управление подзадачами
                var subtaskIndex = 1;
                
                $('#add-subtask').on('click', function() {
                    var subtaskHtml = '<div class=\"subtask-item mb-2\">' +
                        '<div class=\"input-group\">' +
                        '<input type=\"text\" name=\"subtasks[]\" class=\"form-control\" placeholder=\"Например: ' + (subtaskIndex + 1) + '. Следующий этап\">' +
                        '<input type=\"hidden\" name=\"subtask_completed[]\" value=\"0\">' +
                        '<button type=\"button\" class=\"btn btn-danger remove-subtask\">' +
                        '<i class=\"fas fa-times\"></i>' +
                        '</button>' +
                        '</div>' +
                        '</div>';
                    $('#subtasks-container').append(subtaskHtml);
                    subtaskIndex++;
                    updateRemoveButtons();
                });
                
                $(document).on('click', '.remove-subtask', function() {
                    $(this).closest('.subtask-item').remove();
                    updateRemoveButtons();
                });
                
                function updateRemoveButtons() {
                    var items = $('#subtasks-container .subtask-item');
                    if (items.length > 1) {
                        items.find('.remove-subtask').show();
                    } else {
                        items.find('.remove-subtask').hide();
                    }
                }
                
                updateRemoveButtons();
                ");
            endif;
            ?>
        <?php endif; ?>

    </div>

</div>
