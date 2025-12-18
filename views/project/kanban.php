<?php

use yii\helpers\Html;
use app\models\Project;
use app\models\Task;
use app\models\User;

/** @var yii\web\View $this */
/** @var app\models\Project $model */

$this->title = 'Канбан-доска: ' . $model->title;
$this->params['breadcrumbs'][] = ['label' => 'Проекты', 'url' => ['project/index']];
$this->params['breadcrumbs'][] = ['label' => $model->title, 'url' => ['project/view', 'id' => (string)$model->_id]];
$this->params['breadcrumbs'][] = 'Канбан-доска';

$user = Yii::$app->user->identity;
$tasksQuery = Task::find()->where(['project_id' => $model->_id]);

// Фильтруем задачи по видимости для исполнителя
if ($user->role === User::ROLE_EXECUTOR) {
    $allTasks = Task::find()->where(['project_id' => $model->_id])->all();
    $visibleTaskIds = [];
    foreach ($allTasks as $task) {
        if ($task->isAssignedToUser($user)) {
            $visibleTaskIds[] = $task->_id;
        }
    }
    if (!empty($visibleTaskIds)) {
        $tasksQuery->andWhere(['_id' => ['$in' => $visibleTaskIds]]);
    } else {
        $tasksQuery->andWhere(['_id' => ['$in' => []]]); // Пустой результат
    }
}

$tasks = $tasksQuery->all();
$tasksByStatus = [
    Task::STATUS_TODO => [],
    Task::STATUS_IN_PROGRESS => [],
    Task::STATUS_REVIEW => [],
    Task::STATUS_DONE => [],
    Task::STATUS_CANCELED => [],
];

foreach ($tasks as $task) {
    if (isset($tasksByStatus[$task->status])) {
        $tasksByStatus[$task->status][] = $task;
    }
}
?>
<div class="project-kanban">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1><?= Html::encode($model->title) ?></h1>
            <p class="text-muted mb-0">Канбан-доска задач</p>
        </div>
        <div>
            <?php 
            // Менеджер, топ-менеджер, ректор и админ могут создавать задачи
            $canCreateTask = in_array($user->role, [User::ROLE_MANAGER, User::ROLE_TOP_MANAGER, User::ROLE_RECTOR, User::ROLE_ADMIN]);
            if ($canCreateTask): ?>
                <?= Html::a('Создать задачу', ['task/create', 'project_id' => (string)$model->_id], ['class' => 'btn btn-success']) ?>
            <?php endif; ?>
            <?= Html::a('Вернуться к проекту', ['project/view', 'id' => (string)$model->_id], ['class' => 'btn btn-secondary']) ?>
        </div>
    </div>

    <div class="kanban-board">
        <div class="row">
            <!-- К выполнению -->
            <div class="col-md-3 mb-3">
                <div class="kanban-column" data-status="<?= Task::STATUS_TODO ?>">
                    <h5 class="kanban-header bg-secondary text-white p-2 rounded-top">
                        К выполнению
                        <span class="badge badge-light float-right" id="count-<?= Task::STATUS_TODO ?>"><?= count($tasksByStatus[Task::STATUS_TODO]) ?></span>
                    </h5>
                    <div class="kanban-body" id="column-<?= Task::STATUS_TODO ?>">
                        <?php foreach ($tasksByStatus[Task::STATUS_TODO] as $task): ?>
                            <?= $this->render('_task-card-draggable', ['task' => $task, 'user' => $user]) ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- В работе -->
            <div class="col-md-3 mb-3">
                <div class="kanban-column" data-status="<?= Task::STATUS_IN_PROGRESS ?>">
                    <h5 class="kanban-header bg-primary text-white p-2 rounded-top">
                        В работе
                        <span class="badge badge-light float-right" id="count-<?= Task::STATUS_IN_PROGRESS ?>"><?= count($tasksByStatus[Task::STATUS_IN_PROGRESS]) ?></span>
                    </h5>
                    <div class="kanban-body" id="column-<?= Task::STATUS_IN_PROGRESS ?>">
                        <?php foreach ($tasksByStatus[Task::STATUS_IN_PROGRESS] as $task): ?>
                            <?= $this->render('_task-card-draggable', ['task' => $task, 'user' => $user]) ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- На проверке -->
            <div class="col-md-3 mb-3">
                <div class="kanban-column" data-status="<?= Task::STATUS_REVIEW ?>">
                    <h5 class="kanban-header bg-warning text-white p-2 rounded-top">
                        На проверке
                        <span class="badge badge-light float-right" id="count-<?= Task::STATUS_REVIEW ?>"><?= count($tasksByStatus[Task::STATUS_REVIEW]) ?></span>
                    </h5>
                    <div class="kanban-body" id="column-<?= Task::STATUS_REVIEW ?>">
                        <?php foreach ($tasksByStatus[Task::STATUS_REVIEW] as $task): ?>
                            <?= $this->render('_task-card-draggable', ['task' => $task, 'user' => $user]) ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Выполнено -->
            <div class="col-md-3 mb-3">
                <div class="kanban-column" data-status="<?= Task::STATUS_DONE ?>">
                    <h5 class="kanban-header bg-success text-white p-2 rounded-top">
                        Выполнено
                        <span class="badge badge-light float-right" id="count-<?= Task::STATUS_DONE ?>"><?= count($tasksByStatus[Task::STATUS_DONE]) ?></span>
                    </h5>
                    <div class="kanban-body" id="column-<?= Task::STATUS_DONE ?>">
                        <?php foreach ($tasksByStatus[Task::STATUS_DONE] as $task): ?>
                            <?= $this->render('_task-card-draggable', ['task' => $task, 'user' => $user]) ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<style>
.kanban-board {
    margin-top: 20px;
}

.kanban-column {
    min-height: 500px;
    background-color: #f8f9fa;
    border-radius: 0.25rem;
    border: 2px solid #dee2e6;
}

.kanban-header {
    font-weight: bold;
    margin-bottom: 0;
    cursor: default;
}

.kanban-body {
    min-height: 450px;
    padding: 10px;
    background-color: #fff;
    border-radius: 0 0 0.25rem 0.25rem;
}

.kanban-body.drag-over {
    background-color: #e7f3ff;
    border: 2px dashed #007bff;
}

.task-card-draggable {
    cursor: move;
    margin-bottom: 10px;
    transition: transform 0.2s, box-shadow 0.2s;
}

.task-card-draggable:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.15) !important;
}

.task-card-draggable.dragging {
    opacity: 0.5;
    transform: rotate(5deg);
}

.task-card-draggable .card {
    border-left: 3px solid;
    transition: transform 0.2s, box-shadow 0.2s;
}

.task-card-draggable[data-priority="critical"] .card {
    border-left-color: #dc3545;
}

.task-card-draggable[data-priority="high"] .card {
    border-left-color: #ffc107;
}

.task-card-draggable[data-priority="medium"] .card {
    border-left-color: #17a2b8;
}

.task-card-draggable[data-priority="low"] .card {
    border-left-color: #6c757d;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Инициализируем Sortable для каждой колонки
    const columns = document.querySelectorAll('.kanban-body');
    
    columns.forEach(function(column) {
        new Sortable(column, {
            group: 'kanban',
            animation: 150,
            ghostClass: 'dragging',
            dragClass: 'dragging',
            onEnd: function(evt) {
                const taskId = evt.item.dataset.taskId;
                const newStatus = evt.to.closest('.kanban-column').dataset.status;
                const oldStatus = evt.from.closest('.kanban-column').dataset.status;
                
                // Если статус не изменился, ничего не делаем
                if (newStatus === oldStatus) {
                    return;
                }
                
                // Обновляем счетчики
                updateCounters();
                
                // Отправляем AJAX запрос для изменения статуса
                changeTaskStatus(taskId, newStatus);
            }
        });
    });
    
    // Функция для обновления счетчиков
    function updateCounters() {
        const statuses = ['<?= Task::STATUS_TODO ?>', '<?= Task::STATUS_IN_PROGRESS ?>', '<?= Task::STATUS_REVIEW ?>', '<?= Task::STATUS_DONE ?>'];
        statuses.forEach(function(status) {
            const column = document.getElementById('column-' + status);
            const count = column ? column.children.length : 0;
            const countElement = document.getElementById('count-' + status);
            if (countElement) {
                countElement.textContent = count;
            }
        });
    }
    
    // Функция для изменения статуса задачи через AJAX
    function changeTaskStatus(taskId, newStatus) {
        const url = '<?= \yii\helpers\Url::to(['task/change-status']) ?>';
        const csrfToken = '<?= Yii::$app->request->csrfToken ?>';
        
        const formData = new FormData();
        formData.append('<?= Yii::$app->request->csrfParam ?>', csrfToken);
        
        fetch(url + '?id=' + taskId + '&status=' + newStatus, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: formData,
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Показываем уведомление об успехе
                showNotification('Статус задачи успешно изменен', 'success');
            } else {
                // Если ошибка, возвращаем задачу обратно
                showNotification(data.message || 'Ошибка при изменении статуса', 'error');
                location.reload(); // Перезагружаем страницу для восстановления состояния
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Ошибка при изменении статуса', 'error');
            location.reload();
        });
    }
    
    // Функция для показа уведомлений
    function showNotification(message, type) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const notification = document.createElement('div');
        notification.className = 'alert ' + alertClass + ' alert-dismissible fade show position-fixed';
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        notification.innerHTML = `
            ${message}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        `;
        document.body.appendChild(notification);
        
        // Автоматически скрываем через 3 секунды
        setTimeout(function() {
            notification.remove();
        }, 3000);
    }
    
    // Добавляем визуальную обратную связь при перетаскивании
    columns.forEach(function(column) {
        column.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('drag-over');
        });
        
        column.addEventListener('dragleave', function(e) {
            this.classList.remove('drag-over');
        });
        
        column.addEventListener('drop', function(e) {
            this.classList.remove('drag-over');
        });
    });
});
</script>

