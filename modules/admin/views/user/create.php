<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\User;

/** @var yii\web\View $this */
/** @var app\models\User $model */
/** @var yii\widgets\ActiveForm $form */

$this->title = 'Создание пользователя';
$this->params['breadcrumbs'][] = ['label' => 'Админка', 'url' => ['/admin']];
$this->params['breadcrumbs'][] = ['label' => 'Пользователи', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="user-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="user-form">

        <?php $form = ActiveForm::begin(); ?>

        <?= $form->field($model, 'fio')->textInput(['maxlength' => true]) ?>

        <?= $form->field($model, 'email')->textInput(['maxlength' => true, 'type' => 'email']) ?>

        <?= $form->field($model, 'password')->passwordInput(['maxlength' => true]) ?>

        <?= $form->field($model, 'role')->dropDownList([
            User::ROLE_ADMIN => 'Администратор',
            User::ROLE_HEAD => 'Руководитель',
            User::ROLE_RECTOR => 'Ректор',
            User::ROLE_TOP_MANAGER => 'Топ-менеджер',
            User::ROLE_MANAGER => 'Менеджер',
            User::ROLE_EXECUTOR => 'Исполнитель',
        ], ['prompt' => 'Выберите роль']) ?>

        <?php
        // Получаем только основные подразделения
        $departments = \app\models\Department::find()
            ->where(['parent_id' => null])
            ->orderBy(['name' => SORT_ASC])
            ->all();
        $departmentList = [];
        foreach ($departments as $dept) {
            $departmentList[(string)$dept->_id] = $dept->name;
        }
        ?>
        <?= $form->field($model, 'department_id')->dropDownList(
            $departmentList,
            ['prompt' => 'Выберите подразделение', 'id' => 'user-department-id']
        ) ?>

        <?php
        // Получаем все департаменты для JavaScript
        $allSubdepartments = \app\models\Department::find()
            ->where(['!=', 'parent_id', null])
            ->orderBy(['name' => SORT_ASC])
            ->all();
        $subdepartmentsByParent = [];
        foreach ($allSubdepartments as $subdept) {
            $parentId = (string)$subdept->parent_id;
            if (!isset($subdepartmentsByParent[$parentId])) {
                $subdepartmentsByParent[$parentId] = [];
            }
            $subdepartmentsByParent[$parentId][(string)$subdept->_id] = $subdept->name;
        }
        ?>
        <?= $form->field($model, 'subdepartment_id')->dropDownList(
            [],
            ['prompt' => 'Выберите департамент (необязательно)', 'id' => 'user-subdepartment-id']
        )->hint('Сначала выберите подразделение') ?>

        <div class="form-group">
            <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
            <?= Html::a('Отмена', ['index'], ['class' => 'btn btn-secondary']) ?>
        </div>

        <?php ActiveForm::end(); ?>

    </div>

</div>

<?php
$subdepartmentsJson = json_encode($subdepartmentsByParent);
$this->registerJs("
var subdepartments = {$subdepartmentsJson};
var departmentSelect = $('#user-department-id');
var subdepartmentSelect = $('#user-subdepartment-id');

departmentSelect.on('change', function() {
    var selectedDepartmentId = $(this).val();
    subdepartmentSelect.empty();
    subdepartmentSelect.append('<option value=\"\">Выберите департамент (необязательно)</option>');
    
    if (selectedDepartmentId && subdepartments[selectedDepartmentId]) {
        $.each(subdepartments[selectedDepartmentId], function(id, name) {
            subdepartmentSelect.append('<option value=\"' + id + '\">' + name + '</option>');
        });
    }
});

// Инициализация при загрузке страницы
if (departmentSelect.val()) {
    departmentSelect.trigger('change');
    if ('" . ($model->subdepartment_id ? (string)$model->subdepartment_id : '') . "') {
        subdepartmentSelect.val('" . ($model->subdepartment_id ? (string)$model->subdepartment_id : '') . "');
    }
}
");
?>

