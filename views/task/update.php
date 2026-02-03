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

$this->title = 'Редактирование задачи';
$this->params['breadcrumbs'][] = ['label' => 'Проекты', 'url' => ['project/index']];
$this->params['breadcrumbs'][] = ['label' => $project->title, 'url' => ['project/view', 'id' => (string)$project->_id]];
$this->params['breadcrumbs'][] = $this->title;

$user = Yii::$app->user->identity;
$userGlobalRole = $project->isGlobal() ? GlobalProjectRole::getUserRole($user->_id) : null;
// Исполнитель и глоб. исполнитель могут редактировать только ограниченные поля
$isExecutor = ($user->role === User::ROLE_EXECUTOR || $userGlobalRole === GlobalProjectRole::ROLE_GLOBAL_EXECUTOR) && $model->isAssignedToUser($user);
// Полный доступ: менеджер, топ-менеджер, ректор, админ или глоб. руководитель/топ-менеджер/менеджер
$canEditAll = in_array($user->role, [User::ROLE_MANAGER, User::ROLE_TOP_MANAGER, User::ROLE_HEAD, User::ROLE_ADMIN]) ||
    ($project->isGlobal() && in_array($userGlobalRole, [GlobalProjectRole::ROLE_RECTOR, GlobalProjectRole::ROLE_GLOBAL_TOP_MANAGER, GlobalProjectRole::ROLE_GLOBAL_MANAGER]));

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
} elseif (!$project->isGlobal() && $model->executor_user_ids && is_array($model->executor_user_ids) && count($model->executor_user_ids) > 0) {
    $currentExecutorType = 'search_any';
}

// Получаем подразделение проекта
$department = $project->department;
$isGlobalProject = $project->isGlobal();

// Получаем подзадачи для использования в JavaScript
$subtasks = is_array($model->subtasks) ? $model->subtasks : [];
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

        <?php
        $milestones = $milestones ?? [];
        if (!empty($milestones)):
            $milestoneOptions = ['' => '— Не привязан к этапу —'];
            foreach ($milestones as $idx => $m) {
                $name = $m['name'] ?? 'Этап ' . ($idx + 1);
                $deadline = isset($m['deadline']) && $m['deadline'] ? ' (до ' . $m['deadline'] . ')' : '';
                $milestoneOptions[$idx] = $name . $deadline;
            }
            if (!$isExecutor && $canEditAll): ?>
                <?= $form->field($model, 'milestone_index')->dropDownList($milestoneOptions, ['prompt' => '— Не привязан к этапу —']) ?>
            <?php else: ?>
                <div class="form-group">
                    <label class="control-label">Этап (из ТЗ)</label>
                    <div><?= Html::encode($model->getMilestoneName() ?: '— Не привязан —') ?></div>
                </div>
            <?php endif;
        endif; ?>

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

        <?php if ($isGlobalProject): ?>
            <h4>Назначение исполнителей</h4>
            <div class="alert alert-info">
                <strong>Глобальный проект</strong> - можно назначить несколько исполнителей
            </div>
            <?php
            $searchUrl = \yii\helpers\Url::to(['task/search-users']);
            $selectedExecutors = [];
            $selectedExecutorsJs = '';
            if ($model->executor_user_ids && is_array($model->executor_user_ids)) {
                foreach ($model->executor_user_ids as $userId) {
                    // Безопасное преобразование ID в строку
                    if ($userId instanceof \MongoDB\BSON\ObjectId) {
                        $userIdStr = (string)$userId;
                    } elseif (is_string($userId)) {
                        $userIdStr = $userId;
                    } else {
                        $userIdStr = strval($userId);
                    }
                    $user = User::findOne(['_id' => $userIdStr]);
                    if ($user) {
                        $selectedExecutors[] = [
                            'id' => $userIdStr,
                            'text' => $user->fio . ' (' . $user->email . ')'
                        ];
                        
                        // Генерируем JavaScript код заранее
                        $executorId = json_encode($userIdStr, JSON_UNESCAPED_UNICODE);
                        $executorText = json_encode($user->fio . ' (' . $user->email . ')', JSON_UNESCAPED_UNICODE);
                        $selectedExecutorsJs .= "selectedExecutors[{$executorId}] = { id: {$executorId}, text: {$executorText} };\n";
                    }
                }
            }
            ?>
            <div class="form-group field-task-executor_user_ids executor-search-wrapper">
                <label class="control-label">Исполнители</label>
                <div class="executor-search-container">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" 
                               id="executor-search" 
                               class="form-control" 
                               placeholder="Введите минимум 3 символа для поиска..."
                               autocomplete="off">
                        <button type="button" 
                                class="btn btn-primary" 
                                id="executor-search-button"
                                title="Найти пользователей">
                            <i class="fas fa-search"></i> Найти
                        </button>
                        <span class="input-group-text search-loader" style="display: none;">
                            <i class="fas fa-spinner fa-spin"></i>
                        </span>
                    </div>
                    <input type="hidden" name="Task[executor_user_ids]" id="executor-ids-input" value="<?= htmlspecialchars(json_encode(array_column($selectedExecutors, 'id'))) ?>">
                    <div id="executor-search-results" class="executor-search-results"></div>
                    <div class="help-block"></div>
                    <div id="selected-executors" class="mt-2">
                        <?php foreach ($selectedExecutors as $executor): ?>
                            <span class="badge bg-primary fs-6 p-2 me-2 mb-2 selected-executor-badge">
                                <i class="fas fa-user me-2"></i>
                                <?= Html::encode($executor['text']) ?>
                                <button type="button" class="btn-close btn-close-white ms-2 remove-executor" data-user-id="<?= Html::encode($executor['id']) ?>" style="font-size: 0.7em;"></button>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <?php
                // Поиск ответственных (любых пользователей)
                $responsibleSearchUrl = \yii\helpers\Url::to(['task/search-global-managers']);
                $selectedResponsibles = [];
                $selectedResponsiblesJs = '';
                // Проверяем новое поле responsible_user_ids
                if ($model->responsible_user_ids && is_array($model->responsible_user_ids)) {
                    foreach ($model->responsible_user_ids as $userId) {
                        // Безопасное преобразование ID в строку
                        if ($userId instanceof \MongoDB\BSON\ObjectId) {
                            $userIdStr = (string)$userId;
                        } elseif (is_string($userId)) {
                            $userIdStr = $userId;
                        } else {
                            $userIdStr = strval($userId);
                        }
                        $user = User::findOne(['_id' => $userIdStr]);
                        if ($user) {
                            $selectedResponsibles[] = [
                                'id' => $userIdStr,
                                'text' => $user->fio . ' (' . $user->email . ')'
                            ];
                            
                            // Генерируем JavaScript код заранее
                            $responsibleId = json_encode($userIdStr, JSON_UNESCAPED_UNICODE);
                            $responsibleText = json_encode($user->fio . ' (' . $user->email . ')', JSON_UNESCAPED_UNICODE);
                            $selectedResponsiblesJs .= "selectedResponsibles[{$responsibleId}] = { id: {$responsibleId}, text: {$responsibleText} };\n";
                        }
                    }
                }
                // Для обратной совместимости: проверяем старое поле responsible_user_id
                elseif ($model->responsible_user_id) {
                    $responsibleIdStr = $model->responsible_user_id instanceof \MongoDB\BSON\ObjectId 
                        ? (string)$model->responsible_user_id 
                        : (string)$model->responsible_user_id;
                    $responsibleUser = User::findOne(['_id' => $responsibleIdStr]);
                    if ($responsibleUser) {
                        $selectedResponsibles[] = [
                            'id' => $responsibleIdStr,
                            'text' => $responsibleUser->fio . ' (' . $responsibleUser->email . ')'
                        ];
                        $responsibleId = json_encode($responsibleIdStr, JSON_UNESCAPED_UNICODE);
                        $responsibleText = json_encode($responsibleUser->fio . ' (' . $responsibleUser->email . ')', JSON_UNESCAPED_UNICODE);
                        $selectedResponsiblesJs .= "selectedResponsibles[{$responsibleId}] = { id: {$responsibleId}, text: {$responsibleText} };\n";
                    }
                }
                ?>
                <div class="form-group field-task-responsible_user_ids responsible-search-wrapper">
                    <label class="control-label">Ответственные</label>
                    <div class="responsible-search-container">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user-tie"></i></span>
                            <input type="text" 
                                   id="responsible-search" 
                                   class="form-control" 
                                   placeholder="Введите минимум 2 символа для поиска..."
                                   autocomplete="off">
                            <button type="button" 
                                    class="btn btn-primary" 
                                    id="responsible-search-button"
                                    title="Найти пользователей">
                                <i class="fas fa-search"></i> Найти
                            </button>
                            <span class="input-group-text search-loader-responsible" style="display: none;">
                                <i class="fas fa-spinner fa-spin"></i>
                            </span>
                        </div>
                        <input type="hidden" name="Task[responsible_user_ids]" id="responsible-ids-input" value="<?= htmlspecialchars(json_encode(array_column($selectedResponsibles, 'id'))) ?>">
                        <div id="responsible-search-results" class="responsible-search-results"></div>
                        <div class="help-block"></div>
                        <div id="selected-responsibles" class="mt-2">
                            <?php foreach ($selectedResponsibles as $responsible): ?>
                                <span class="badge bg-primary fs-6 p-2 me-2 mb-2 selected-responsible-badge">
                                    <i class="fas fa-user-tie me-2"></i>
                                    <?= Html::encode($responsible['text']) ?>
                                    <button type="button" class="btn-close btn-close-white ms-2 remove-responsible" data-user-id="<?= Html::encode($responsible['id']) ?>" style="font-size: 0.7em;"></button>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php elseif (!$isExecutor && $department): ?>
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
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="executor_type" id="executor_type_search_any" value="search_any" <?= $currentExecutorType === 'search_any' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="executor_type_search_any">
                            Поиск любого сотрудника
                        </label>
                    </div>
                </div>
            </div>

            <?php
            // Поиск любого пользователя (как при создании)
            $searchAnyUsersUrl = \yii\helpers\Url::to(['task/search-users', 'exclude_department_id' => $department ? (string)$department->_id : '']);
            $selectedAnyExecutors = [];
            if ($currentExecutorType === 'search_any' && $model->executor_user_ids && is_array($model->executor_user_ids)) {
                foreach ($model->executor_user_ids as $uid) {
                    $uidStr = $uid instanceof \MongoDB\BSON\ObjectId ? (string)$uid : (string)$uid;
                    $u = User::findOne(['_id' => $uidStr]);
                    if ($u) {
                        $selectedAnyExecutors[] = ['id' => $uidStr, 'text' => $u->fio . ' (' . $u->email . ')'];
                    }
                }
            }
            $approvedRequestUserIds = [];
            foreach (\app\models\TaskExecutorRequest::getAllByTask($model->_id) as $req) {
                if ($req->status === \app\models\TaskExecutorRequest::STATUS_APPROVED && $req->user_id) {
                    $approvedRequestUserIds[(string)$req->user_id] = true;
                }
            }
            foreach (array_keys($approvedRequestUserIds) as $uidStr) {
                if (in_array($uidStr, array_column($selectedAnyExecutors, 'id'))) continue;
                $u = User::findOne(['_id' => $uidStr]);
                if ($u) $selectedAnyExecutors[] = ['id' => $uidStr, 'text' => $u->fio . ' (' . $u->email . ')'];
            }
            $anyExecutorIdsJson = json_encode(array_column($selectedAnyExecutors, 'id'));
            ?>

            <div id="executor_search_any_field" style="display: <?= $currentExecutorType === 'search_any' ? 'block' : 'none' ?>;">
                <div class="form-group field-task-executor_any_user_ids any-executor-search-wrapper">
                    <label class="control-label">Исполнители (поиск по ФИО или email)</label>
                    <div class="any-executor-search-container">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text"
                                   id="any-executor-search"
                                   class="form-control"
                                   placeholder="Введите минимум 3 символа для поиска..."
                                   autocomplete="off">
                            <button type="button"
                                    class="btn btn-primary"
                                    id="any-executor-search-button"
                                    title="Найти пользователей">
                                <i class="fas fa-search"></i> Найти
                            </button>
                            <span class="input-group-text any-search-loader" style="display: none;">
                                <i class="fas fa-spinner fa-spin"></i>
                            </span>
                        </div>
                        <input type="hidden" name="Task[executor_user_ids]" id="any-executor-ids-input" value="<?= Html::encode($anyExecutorIdsJson) ?>">
                        <div id="any-executor-search-results" class="any-executor-search-results"></div>
                        <div class="alert alert-info mt-2 small">
                            Сотрудник без подразделения прикрепляется к задаче сразу. Сотрудник из другого подразделения — после одобрения заявки его руководителем или топ-менеджером.
                        </div>
                        <div id="selected-any-executors" class="mt-2">
                            <?php foreach ($selectedAnyExecutors as $ex): ?>
                                <span class="badge bg-primary fs-6 p-2 me-2 mb-2 selected-any-executor-badge">
                                    <i class="fas fa-user me-2"></i><?= Html::encode($ex['text']) ?>
                                    <button type="button" class="btn-close btn-close-white ms-2 remove-any-executor" data-user-id="<?= Html::encode($ex['id']) ?>" style="font-size: 0.7em;"></button>
                                </span>
                            <?php endforeach; ?>
                        </div>
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

            <div id="executor_user_from_department_field" style="display: <?= in_array($currentExecutorType, ['user_from_department']) ? 'block' : 'none' ?>;">
                <?= $form->field($model, 'executor_user_from_department_id')->dropDownList(
                    $usersFromDepartmentList,
                    ['prompt' => 'Выберите пользователя из подразделения']
                ) ?>
            </div>

            <div id="executor_subdepartment_field" style="display: <?= in_array($currentExecutorType, ['subdepartment']) ? 'block' : 'none' ?>;">
                <?= $form->field($model, 'executor_subdepartment_id')->dropDownList(
                    $subdepartmentsList,
                    ['prompt' => 'Выберите департамент']
                ) ?>
            </div>

            <div id="executor_user_from_subdepartment_field" style="display: <?= in_array($currentExecutorType, ['user_from_subdepartment']) ? 'block' : 'none' ?>;">
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

            <?php if ($isGlobalProject): ?>
                <?php
                // JavaScript для подзадач остается в registerJs
                $this->registerJs("
                // Управление подзадачами
                var subtaskIndex = " . (count($subtasks) + 1) . ";
                
                $('#add-subtask').on('click', function() {
                    var subtaskHtml = '<div class=\"subtask-item mb-2\">' +
                        '<div class=\"input-group\">' +
                        '<input type=\"text\" name=\"subtasks[]\" class=\"form-control\" placeholder=\"Например: ' + subtaskIndex + '. Следующий этап\">' +
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
                ?>
            <?php else: ?>
            <?php
            $this->registerJs("
            // Управление подзадачами
            var subtaskIndex = " . (count($subtasks) + 1) . ";
            
            $('#add-subtask').on('click', function() {
                var subtaskHtml = '<div class=\"subtask-item mb-2\">' +
                    '<div class=\"input-group\">' +
                    '<input type=\"text\" name=\"subtasks[]\" class=\"form-control\" placeholder=\"Например: ' + subtaskIndex + '. Следующий этап\">' +
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
            var currentSubdepartmentId = '{$selectedSubdepartmentForUser}';
            
            $('input[name=\"executor_type\"]').on('change', function() {
                var type = $(this).val();
                $('#executor_user_from_department_field').hide();
                $('#executor_subdepartment_field').hide();
                $('#executor_user_from_subdepartment_field').hide();
                $('#executor_search_any_field').hide();
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
                } else if (type === 'search_any') {
                    $('#executor_search_any_field').show();
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
            <script>
            (function() {
                var searchUrl = <?= json_encode($searchAnyUsersUrl) ?>;
                var minLength = 3;
                document.addEventListener('DOMContentLoaded', function() {
                    var searchInput = document.getElementById('any-executor-search');
                    if (!searchInput) return;
                    var resultsDiv = document.getElementById('any-executor-search-results');
                    var executorIdsInput = document.getElementById('any-executor-ids-input');
                    var selectedExecutorsDiv = document.getElementById('selected-any-executors');
                    var loader = document.querySelector('.any-search-loader');
                    var searchButton = document.getElementById('any-executor-search-button');
                    var searchTimeout;
                    var selectedAnyExecutors = {};
                    [].slice.call(selectedExecutorsDiv.querySelectorAll('.selected-any-executor-badge')).forEach(function(badge) {
                        var btn = badge.querySelector('.remove-any-executor');
                        if (btn) {
                            var uid = btn.getAttribute('data-user-id');
                            var text = badge.textContent.replace(/\s*×\s*$/, '').trim();
                            selectedAnyExecutors[uid] = { id: uid, text: text };
                        }
                    });
                    if (searchButton) {
                        searchButton.addEventListener('click', function(e) {
                            e.preventDefault();
                            var query = searchInput.value.trim();
                            if (query.length < minLength) {
                                resultsDiv.innerHTML = '<div class="any-executor-search-hint">Введите минимум ' + minLength + ' символа для поиска</div>';
                                resultsDiv.style.display = 'block';
                                searchInput.focus();
                                return;
                            }
                            performAnySearch(query);
                        });
                    }
                    function performAnySearch(query) {
                        if (!query || query.length < minLength) { resultsDiv.style.display = 'none'; resultsDiv.innerHTML = ''; if (loader) loader.style.display = 'none'; return; }
                        if (loader) loader.style.display = 'block';
                        resultsDiv.style.display = 'none';
                        resultsDiv.innerHTML = '';
                        var url = searchUrl + (searchUrl.indexOf('?') !== -1 ? '&' : '?') + 'q=' + encodeURIComponent(query);
                        fetch(url, { method: 'GET', headers: { 'Accept': 'application/json' } })
                            .then(function(r) { return r.ok ? r.json() : Promise.reject(new Error('Network error')); })
                            .then(function(response) {
                                if (loader) loader.style.display = 'none';
                                resultsDiv.innerHTML = '';
                                if (response && response.results && response.results.length > 0) {
                                    response.results.forEach(function(user) {
                                        if (user && user.id && user.text && !selectedAnyExecutors[user.id]) {
                                            var item = document.createElement('div');
                                            item.className = 'any-executor-search-item';
                                            item.innerHTML = '<i class="fas fa-user me-2"></i>' + user.text;
                                            item.setAttribute('data-user-id', user.id);
                                            item.setAttribute('data-user-text', user.text);
                                            item.addEventListener('click', function(e) { e.preventDefault(); selectAnyExecutor(user.id, user.text); });
                                            resultsDiv.appendChild(item);
                                        }
                                    });
                                    resultsDiv.style.display = resultsDiv.children.length ? 'block' : 'none';
                                    if (resultsDiv.children.length === 0) resultsDiv.innerHTML = '<div class="any-executor-search-empty">Пользователи не найдены</div>';
                                    if (resultsDiv.children.length) resultsDiv.style.display = 'block';
                                } else {
                                    resultsDiv.innerHTML = '<div class="any-executor-search-empty">Пользователи не найдены</div>';
                                    resultsDiv.style.display = 'block';
                                }
                            })
                            .catch(function() { if (loader) loader.style.display = 'none'; resultsDiv.innerHTML = '<div class="any-executor-search-error">Ошибка при поиске</div>'; resultsDiv.style.display = 'block'; });
                    }
                    function selectAnyExecutor(userId, userText) {
                        if (selectedAnyExecutors[userId]) return;
                        selectedAnyExecutors[userId] = { id: userId, text: userText };
                        updateAnyExecutorIdsInput();
                        searchInput.value = '';
                        resultsDiv.style.display = 'none';
                        var badge = document.createElement('span');
                        badge.className = 'badge bg-primary fs-6 p-2 me-2 mb-2 selected-any-executor-badge';
                        badge.innerHTML = '<i class="fas fa-user me-2"></i>' + userText + ' <button type="button" class="btn-close btn-close-white ms-2 remove-any-executor" data-user-id="' + userId + '" style="font-size: 0.7em;"></button>';
                        selectedExecutorsDiv.appendChild(badge);
                    }
                    function removeAnyExecutor(userId) {
                        if (selectedAnyExecutors[userId]) { delete selectedAnyExecutors[userId]; updateAnyExecutorIdsInput(); var b = selectedExecutorsDiv.querySelector('.remove-any-executor[data-user-id="' + userId + '"]'); if (b) b.closest('.selected-any-executor-badge').remove(); }
                    }
                    function updateAnyExecutorIdsInput() { executorIdsInput.value = JSON.stringify(Object.keys(selectedAnyExecutors)); }
                    selectedExecutorsDiv.addEventListener('click', function(e) { if (e.target && e.target.classList.contains('remove-any-executor')) { e.preventDefault(); removeAnyExecutor(e.target.getAttribute('data-user-id')); } });
                    searchInput.addEventListener('input', function() {
                        var query = this.value.trim();
                        clearTimeout(searchTimeout);
                        if (query.length === 0) { resultsDiv.style.display = 'none'; resultsDiv.innerHTML = ''; return; }
                        if (query.length < minLength) { resultsDiv.innerHTML = '<div class="any-executor-search-hint">Введите еще ' + (minLength - query.length) + ' символов</div>'; resultsDiv.style.display = 'block'; return; }
                        searchTimeout = setTimeout(function() { performAnySearch(query); }, 250);
                    });
                    searchInput.addEventListener('focus', function() { var q = this.value.trim(); if (q.length >= minLength && resultsDiv.children.length) resultsDiv.style.display = 'block'; });
                    document.addEventListener('click', function(e) { var c = document.querySelector('.any-executor-search-container'); if (c && !c.contains(e.target)) resultsDiv.style.display = 'none'; });
                    var form = searchInput.closest('form');
                    if (form) {
                        form.addEventListener('submit', function() {
                            var searchAnyChecked = document.getElementById('executor_type_search_any') && document.getElementById('executor_type_search_any').checked;
                            if (!searchAnyChecked) executorIdsInput.value = '[]';
                            else {
                                var dept = document.getElementById('task-executor_user_from_department_id'); if (dept) dept.value = '';
                                var sub = document.getElementById('task-executor_subdepartment_id'); if (sub) sub.value = '';
                                var subUser = document.getElementById('task-executor_user_from_subdepartment_id'); if (subUser) subUser.value = '';
                            }
                        });
                    }
                });
            })();
            </script>
            <style>
            .any-executor-search-container{position:relative}.any-executor-search-container .input-group{margin-bottom:0}#any-executor-search-results{position:absolute;top:100%;left:0;right:0;z-index:1000;max-height:350px;overflow-y:auto;display:none;margin-top:4px;border:1px solid #dee2e6;border-radius:.375rem;background:#fff;box-shadow:0 .5rem 1rem rgba(0,0,0,.15)}.any-executor-search-item{padding:.75rem 1rem;cursor:pointer;border-bottom:1px solid #f0f0f0;transition:background-color .2s;color:#212529}.any-executor-search-item:last-child{border-bottom:none}.any-executor-search-item:hover{background-color:#e7f1ff;color:#0d6efd}.any-executor-search-hint,.any-executor-search-empty,.any-executor-search-error{padding:.75rem 1rem;text-align:center}.any-executor-search-hint,.any-executor-search-empty{color:#6c757d;font-style:italic}.any-executor-search-error{color:#dc3545}.selected-any-executor-badge{display:inline-flex;align-items:center;font-weight:500}
            </style>
            <?php endif; ?>
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

        <h4>Подзадачи (To-Do лист)</h4>
        <div class="alert alert-info">
            <small>Добавьте этапы выполнения задачи. Прогресс будет рассчитываться автоматически на основе выполненных подзадач.</small>
        </div>
        <div id="subtasks-container">
            <?php
            if (empty($subtasks)) {
                // Если подзадач нет, показываем одно пустое поле
                echo '<div class="subtask-item mb-2">';
                echo '<div class="input-group">';
                echo '<input type="text" name="subtasks[]" class="form-control" placeholder="Например: 1. Первый этап">';
                echo '<input type="hidden" name="subtask_completed[]" value="0">';
                echo '<button type="button" class="btn btn-danger remove-subtask" style="display: none;">';
                echo '<i class="fas fa-times"></i>';
                echo '</button>';
                echo '</div>';
                echo '</div>';
            } else {
                foreach ($subtasks as $index => $subtask) {
                    $text = isset($subtask['text']) ? htmlspecialchars($subtask['text']) : '';
                    $completed = isset($subtask['completed']) && $subtask['completed'] ? '1' : '0';
                    echo '<div class="subtask-item mb-2">';
                    echo '<div class="input-group">';
                    echo '<input type="text" name="subtasks[]" class="form-control" value="' . $text . '" placeholder="Текст подзадачи">';
                    echo '<input type="hidden" name="subtask_completed[]" value="' . $completed . '">';
                    echo '<button type="button" class="btn btn-danger remove-subtask">';
                    echo '<i class="fas fa-times"></i>';
                    echo '</button>';
                    echo '</div>';
                    echo '</div>';
                }
            }
            ?>
        </div>
        <button type="button" class="btn btn-success btn-sm mt-2" id="add-subtask">
            <i class="fas fa-plus me-1"></i>Добавить подзадачу
        </button>

        <div class="form-group mt-3" style="display: none;">
            <?= $form->field($model, 'progress')->hiddenInput()->label(false) ?>
        </div>

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

<?php if ($isGlobalProject): ?>
<style>
.executor-search-wrapper {
    margin-bottom: 1.5rem;
}

.executor-search-container {
    position: relative;
}

.executor-search-container .input-group {
    margin-bottom: 0;
}

.executor-search-container .input-group-text {
    background-color: #f8f9fa;
    border-color: #dee2e6;
}

#executor-search {
    border-left: none;
    border-right: none;
}

#executor-search:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

#executor-search-button {
    border-left: none;
    white-space: nowrap;
    z-index: 0;
}

#executor-search-button:hover {
    z-index: 1;
}

.search-loader {
    background-color: #f8f9fa;
}

#executor-search-results {
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

.executor-search-item {
    padding: 0.75rem 1rem;
    cursor: pointer;
    border-bottom: 1px solid #f0f0f0;
    transition: background-color 0.2s;
    color: #212529;
}

.executor-search-item:last-child {
    border-bottom: none;
}

.executor-search-item:hover,
.executor-search-item.active {
    background-color: #e7f1ff;
    color: #0d6efd;
}

.executor-search-item i {
    color: #6c757d;
}

.executor-search-item:hover i,
.executor-search-item.active i {
    color: #0d6efd;
}

.executor-search-hint {
    padding: 0.75rem 1rem;
    color: #6c757d;
    font-style: italic;
    text-align: center;
}

.executor-search-empty {
    padding: 1rem;
    color: #6c757d;
    text-align: center;
}

.executor-search-error {
    padding: 0.75rem 1rem;
    color: #dc3545;
    text-align: center;
}

#selected-executors {
    margin-top: 0.75rem;
}

.selected-executor-badge {
    display: inline-flex;
    align-items: center;
    font-weight: 500;
}

.selected-executor-badge .btn-close {
    opacity: 0.8;
}

.selected-executor-badge .btn-close:hover {
    opacity: 1;
}

.responsible-search-wrapper {
    margin-bottom: 1.5rem;
}

.responsible-search-container {
    position: relative;
}

.responsible-search-container .input-group {
    margin-bottom: 0;
}

#responsible-search {
    border-left: none;
    border-right: none;
}

#responsible-search-button {
    border-left: none;
    white-space: nowrap;
    z-index: 0;
}

#responsible-search-results {
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

.responsible-search-item {
    padding: 0.75rem 1rem;
    cursor: pointer;
    border-bottom: 1px solid #f0f0f0;
    transition: background-color 0.2s;
    color: #212529;
}

.responsible-search-item:last-child {
    border-bottom: none;
}

.responsible-search-item:hover {
    background-color: #e7f1ff;
    color: #0d6efd;
}

.responsible-search-hint,
.responsible-search-empty,
.responsible-search-error {
    padding: 0.75rem 1rem;
    text-align: center;
}

.responsible-search-hint {
    color: #6c757d;
    font-style: italic;
}

.responsible-search-empty {
    color: #6c757d;
}

.responsible-search-error {
    color: #dc3545;
}

#selected-responsibles {
    margin-top: 0.75rem;
}

.selected-responsible-badge {
    display: inline-flex;
    align-items: center;
    font-weight: 500;
}

.selected-responsible-badge .btn-close {
    opacity: 0.8;
}

.selected-responsible-badge .btn-close:hover {
    opacity: 1;
}

.responsible-search-item:hover,
.responsible-search-item.active {
    background-color: #e7f1ff;
    color: #0d6efd;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var searchInput = document.getElementById('executor-search');
    var resultsDiv = document.getElementById('executor-search-results');
    var executorIdsInput = document.getElementById('executor-ids-input');
    var selectedExecutorsDiv = document.getElementById('selected-executors');
    var loader = document.querySelector('.search-loader');
    var searchButton = document.getElementById('executor-search-button');
    var searchTimeout;
    var searchUrl = '<?= $searchUrl ?>';
    var minLength = 3;
    var selectedExecutors = {};
    
    console.log('Search URL:', searchUrl);
    
    // Загружаем уже выбранных исполнителей
    <?= $selectedExecutorsJs ?>
    
    // Обработчик клика на кнопку поиска
    if (searchButton) {
        searchButton.addEventListener('click', function(e) {
            e.preventDefault();
            var query = searchInput.value.trim();
            
            if (query.length < minLength) {
                resultsDiv.innerHTML = '<div class="executor-search-hint">Введите минимум ' + minLength + ' символа для поиска</div>';
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
                            // Пропускаем уже выбранных пользователей
                            if (selectedExecutors[user.id]) {
                                return;
                            }
                            var item = document.createElement('div');
                            item.className = 'executor-search-item';
                            item.innerHTML = '<i class="fas fa-user me-2"></i>' + user.text;
                            item.setAttribute('data-user-id', user.id);
                            item.setAttribute('data-user-text', user.text);
                            item.addEventListener('click', function(e) {
                                e.preventDefault();
                                selectExecutor(user.id, user.text);
                            });
                            resultsDiv.appendChild(item);
                        }
                    });
                    if (resultsDiv.children.length > 0) {
                        resultsDiv.style.display = 'block';
                    } else {
                        resultsDiv.innerHTML = '<div class="executor-search-empty">Пользователи не найдены</div>';
                        resultsDiv.style.display = 'block';
                    }
                } else {
                    resultsDiv.innerHTML = '<div class="executor-search-empty">Пользователи не найдены</div>';
                    resultsDiv.style.display = 'block';
                }
            } else {
                console.error('Invalid response format:', response);
                resultsDiv.innerHTML = '<div class="executor-search-error">Ошибка: неверный формат ответа от сервера</div>';
                resultsDiv.style.display = 'block';
            }
        })
        .catch(function(error) {
            if (loader) loader.style.display = 'none';
            console.error('Search error:', error);
            resultsDiv.innerHTML = '<div class="executor-search-error">Ошибка при поиске. Попробуйте еще раз.</div>';
            resultsDiv.style.display = 'block';
        });
    }
    
    // Функция выбора исполнителя
    function selectExecutor(userId, userText) {
        if (selectedExecutors[userId]) {
            return; // Уже выбран
        }
        
        selectedExecutors[userId] = {
            id: userId,
            text: userText
        };
        
        updateExecutorIdsInput();
        searchInput.value = '';
        resultsDiv.style.display = 'none';
        
        // Добавляем badge
        var badge = document.createElement('span');
        badge.className = 'badge bg-primary fs-6 p-2 me-2 mb-2 selected-executor-badge';
        badge.innerHTML = '<i class="fas fa-user me-2"></i>' + userText +
            ' <button type="button" class="btn-close btn-close-white ms-2 remove-executor" data-user-id="' + userId + '" style="font-size: 0.7em;"></button>';
        selectedExecutorsDiv.appendChild(badge);
    }
    
    // Функция удаления исполнителя
    function removeExecutor(userId) {
        if (selectedExecutors[userId]) {
            delete selectedExecutors[userId];
            updateExecutorIdsInput();
            var badge = document.querySelector('.remove-executor[data-user-id="' + userId + '"]');
            if (badge) {
                badge.closest('.selected-executor-badge').remove();
            }
        }
    }
    
    // Обновление скрытого поля с ID исполнителей
    function updateExecutorIdsInput() {
        var ids = Object.keys(selectedExecutors);
        executorIdsInput.value = JSON.stringify(ids);
    }
    
    // Обработчик ввода текста - автоматический поиск
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            var query = this.value.trim();
            
            clearTimeout(searchTimeout);
            
            if (query.length === 0) {
                resultsDiv.style.display = 'none';
                resultsDiv.innerHTML = '';
                return;
            }
            
            // Показываем подсказку, если символов меньше минимума
            if (query.length > 0 && query.length < minLength) {
                resultsDiv.innerHTML = '<div class="executor-search-hint">Введите еще ' + (minLength - query.length) + ' символов для поиска</div>';
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
        var container = document.querySelector('.executor-search-container');
        if (container && !container.contains(e.target)) {
            resultsDiv.style.display = 'none';
        }
    });
    
    // Удаление исполнителя (делегирование событий)
    document.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('remove-executor')) {
            e.preventDefault();
            var userId = e.target.getAttribute('data-user-id');
            removeExecutor(userId);
        }
    });
    
    // Навигация по результатам клавиатурой
    if (searchInput) {
        searchInput.addEventListener('keydown', function(e) {
            var items = resultsDiv.querySelectorAll('.executor-search-item');
            
            if (e.keyCode === 40) { // Стрелка вниз
                e.preventDefault();
                var active = resultsDiv.querySelector('.executor-search-item.active');
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
                var active = resultsDiv.querySelector('.executor-search-item.active');
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
                var active = resultsDiv.querySelector('.executor-search-item.active');
                if (active) {
                    var userId = active.getAttribute('data-user-id');
                    var userText = active.getAttribute('data-user-text');
                    if (userId) {
                        selectExecutor(userId, userText);
                    }
                } else {
                    // Если ничего не выбрано, но есть результаты - выбираем первый
                    if (items.length > 0) {
                        var firstItem = items[0];
                        var userId = firstItem.getAttribute('data-user-id');
                        var userText = firstItem.getAttribute('data-user-text');
                        if (userId) {
                            selectExecutor(userId, userText);
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
    
    // Поиск ответственных (любых пользователей)
    var responsibleSearchInput = document.getElementById('responsible-search');
    var responsibleResultsDiv = document.getElementById('responsible-search-results');
    var responsibleIdsInput = document.getElementById('responsible-ids-input');
    var selectedResponsiblesDiv = document.getElementById('selected-responsibles');
    var responsibleLoader = document.querySelector('.search-loader-responsible');
    var responsibleSearchButton = document.getElementById('responsible-search-button');
    var responsibleSearchTimeout;
    var responsibleSearchUrl = '<?= $responsibleSearchUrl ?>';
    var responsibleMinLength = 2;
    var selectedResponsibles = {};
    
    // Загружаем уже выбранных ответственных
    <?= $selectedResponsiblesJs ?>
    
    // Обработчик клика на кнопку поиска
    if (responsibleSearchButton) {
        responsibleSearchButton.addEventListener('click', function(e) {
            e.preventDefault();
            var query = responsibleSearchInput.value.trim();
            
            if (query.length < responsibleMinLength) {
                responsibleResultsDiv.innerHTML = '<div class="responsible-search-hint">Введите минимум ' + responsibleMinLength + ' символа для поиска</div>';
                responsibleResultsDiv.style.display = 'block';
                responsibleSearchInput.focus();
                return;
            }
            
            performResponsibleSearch(query);
        });
    }
    
    // Функция для выполнения поиска ответственных
    function performResponsibleSearch(query) {
        if (!query || query.length < responsibleMinLength) {
            responsibleResultsDiv.style.display = 'none';
            responsibleResultsDiv.innerHTML = '';
            if (responsibleLoader) responsibleLoader.style.display = 'none';
            return;
        }
        
        if (responsibleLoader) responsibleLoader.style.display = 'block';
        responsibleResultsDiv.style.display = 'none';
        responsibleResultsDiv.innerHTML = '';
        
        var url = responsibleSearchUrl + '?q=' + encodeURIComponent(query);
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
            if (responsibleLoader) responsibleLoader.style.display = 'none';
            responsibleResultsDiv.innerHTML = '';
            
            if (response && response.results !== undefined) {
                if (response.results.length > 0) {
                    response.results.forEach(function(user) {
                        if (user && user.id && user.text) {
                            // Пропускаем уже выбранных пользователей
                            if (selectedResponsibles[user.id]) {
                                return;
                            }
                            var item = document.createElement('div');
                            item.className = 'responsible-search-item';
                            item.innerHTML = '<i class="fas fa-user-tie me-2"></i>' + user.text;
                            item.setAttribute('data-user-id', user.id);
                            item.setAttribute('data-user-text', user.text);
                            item.addEventListener('click', function(e) {
                                e.preventDefault();
                                selectResponsible(user.id, user.text);
                            });
                            responsibleResultsDiv.appendChild(item);
                        }
                    });
                    if (responsibleResultsDiv.children.length > 0) {
                        responsibleResultsDiv.style.display = 'block';
                    } else {
                        responsibleResultsDiv.innerHTML = '<div class="responsible-search-empty">Пользователи не найдены</div>';
                        responsibleResultsDiv.style.display = 'block';
                    }
                } else {
                    responsibleResultsDiv.innerHTML = '<div class="responsible-search-empty">Пользователи не найдены</div>';
                    responsibleResultsDiv.style.display = 'block';
                }
            } else {
                responsibleResultsDiv.innerHTML = '<div class="responsible-search-error">Ошибка: неверный формат ответа от сервера</div>';
                responsibleResultsDiv.style.display = 'block';
            }
        })
        .catch(function(error) {
            if (responsibleLoader) responsibleLoader.style.display = 'none';
            responsibleResultsDiv.innerHTML = '<div class="responsible-search-error">Ошибка при поиске. Попробуйте еще раз.</div>';
            responsibleResultsDiv.style.display = 'block';
        });
    }
    
    // Функция выбора ответственного
    function selectResponsible(userId, userText) {
        if (selectedResponsibles[userId]) {
            return; // Уже выбран
        }
        
        selectedResponsibles[userId] = {
            id: userId,
            text: userText
        };
        
        updateResponsibleIdsInput();
        responsibleSearchInput.value = '';
        responsibleResultsDiv.style.display = 'none';
        
        // Добавляем badge
        var badge = document.createElement('span');
        badge.className = 'badge bg-primary fs-6 p-2 me-2 mb-2 selected-responsible-badge';
        badge.innerHTML = '<i class="fas fa-user-tie me-2"></i>' + userText +
            ' <button type="button" class="btn-close btn-close-white ms-2 remove-responsible" data-user-id="' + userId + '" style="font-size: 0.7em;"></button>';
        selectedResponsiblesDiv.appendChild(badge);
    }
    
    // Функция удаления ответственного
    function removeResponsible(userId) {
        if (selectedResponsibles[userId]) {
            delete selectedResponsibles[userId];
            updateResponsibleIdsInput();
            var badge = document.querySelector('.remove-responsible[data-user-id="' + userId + '"]');
            if (badge) {
                badge.closest('.selected-responsible-badge').remove();
            }
        }
    }
    
    // Обновление скрытого поля с ID ответственных
    function updateResponsibleIdsInput() {
        var ids = Object.keys(selectedResponsibles);
        responsibleIdsInput.value = JSON.stringify(ids);
    }
    
    // Обработчик ввода текста - автоматический поиск
    if (responsibleSearchInput) {
        responsibleSearchInput.addEventListener('input', function() {
            var query = this.value.trim();
            
            clearTimeout(responsibleSearchTimeout);
            
            if (query.length === 0) {
                responsibleResultsDiv.style.display = 'none';
                responsibleResultsDiv.innerHTML = '';
                return;
            }
            
            // Показываем подсказку, если символов меньше минимума
            if (query.length > 0 && query.length < responsibleMinLength) {
                responsibleResultsDiv.innerHTML = '<div class="responsible-search-hint">Введите еще ' + (responsibleMinLength - query.length) + ' символов для поиска</div>';
                responsibleResultsDiv.style.display = 'block';
                return;
            }
            
            // Автоматически выполняем поиск с небольшой задержкой
            responsibleSearchTimeout = setTimeout(function() {
                performResponsibleSearch(query);
            }, 250);
        });
        
        // Обработка фокуса - показываем результаты, если есть текст
        responsibleSearchInput.addEventListener('focus', function() {
            var query = this.value.trim();
            if (query.length >= responsibleMinLength) {
                if (responsibleResultsDiv.children.length === 0) {
                    performResponsibleSearch(query);
                } else {
                    responsibleResultsDiv.style.display = 'block';
                }
            }
        });
    }
    
    // Скрываем результаты при клике вне области поиска
    document.addEventListener('click', function(e) {
        var container = document.querySelector('.responsible-search-container');
        if (container && !container.contains(e.target)) {
            responsibleResultsDiv.style.display = 'none';
        }
    });
    
    // Удаление ответственного (делегирование событий)
    document.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('remove-responsible')) {
            e.preventDefault();
            var userId = e.target.getAttribute('data-user-id');
            removeResponsible(userId);
        }
    });
    
    // Навигация по результатам клавиатурой
    if (responsibleSearchInput) {
        responsibleSearchInput.addEventListener('keydown', function(e) {
            var items = responsibleResultsDiv.querySelectorAll('.responsible-search-item');
            
            if (e.keyCode === 40) { // Стрелка вниз
                e.preventDefault();
                var active = responsibleResultsDiv.querySelector('.responsible-search-item.active');
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
                var active = responsibleResultsDiv.querySelector('.responsible-search-item.active');
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
                var active = responsibleResultsDiv.querySelector('.responsible-search-item.active');
                if (active) {
                    var userId = active.getAttribute('data-user-id');
                    var userText = active.getAttribute('data-user-text');
                    if (userId) {
                        selectResponsible(userId, userText);
                    }
                } else {
                    // Если ничего не выбрано, но есть результаты - выбираем первый
                    if (items.length > 0) {
                        var firstItem = items[0];
                        var userId = firstItem.getAttribute('data-user-id');
                        var userText = firstItem.getAttribute('data-user-text');
                        if (userId) {
                            selectResponsible(userId, userText);
                        }
                    } else {
                        // Если результатов нет, запускаем поиск
                        var query = responsibleSearchInput.value.trim();
                        if (query.length >= responsibleMinLength) {
                            performResponsibleSearch(query);
                        }
                    }
                }
            } else if (e.keyCode === 27) { // Escape
                e.preventDefault();
                responsibleResultsDiv.style.display = 'none';
            }
        });
    }
});
</script>
<?php endif; ?>
