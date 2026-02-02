<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\models\TaskExecutorRequest;
use app\models\Task;

/** @var yii\web\View $this */
/** @var TaskExecutorRequest[] $requests */

$this->title = 'Заявки на прикрепление к задаче';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="task-executor-request-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <p class="text-muted">Заявки на прикрепление сотрудников вашего подразделения к задачам других подразделений. Одобрите или отклоните заявку.</p>

    <?php if (empty($requests)): ?>
        <div class="nku-card">
            <div class="nku-card__body text-center text-muted py-5">
                Нет ожидающих заявок.
            </div>
        </div>
    <?php else: ?>
        <div class="nku-card">
            <div class="nku-card__body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Задача</th>
                                <th>Исполнитель</th>
                                <th>Кто запросил</th>
                                <th>Проект</th>
                                <th width="220">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $req): ?>
                                <?php
                                $task = $req->task;
                                $executor = $req->user;
                                $requestedBy = $req->requestedByUser;
                                $project = $task ? $task->project : null;
                                $taskTitle = $task ? Html::encode($task->title) : '—';
                                $taskDesc = $task ? Html::encode($task->description ?: 'Нет описания') : '—';
                                $taskStatus = $task ? $task->getStatusLabel() : '—';
                                $taskPriority = $task ? $task->getPriorityLabel() : '—';
                                $projectTitle = $project ? Html::encode($project->title) : '—';
                                $taskStart = $task && $task->start_date instanceof \MongoDB\BSON\UTCDateTime ? date('d.m.Y', $task->start_date->toDateTime()->getTimestamp()) : '—';
                                $taskDue = $task && $task->due_date instanceof \MongoDB\BSON\UTCDateTime ? date('d.m.Y', $task->due_date->toDateTime()->getTimestamp()) : '—';
                                $taskData = htmlspecialchars(json_encode([
                                    'title' => $task ? $task->title : '',
                                    'description' => $task ? ($task->description ?: 'Нет описания') : '',
                                    'status' => $taskStatus,
                                    'priority' => $taskPriority,
                                    'project' => $project ? $project->title : '',
                                    'start_date' => $taskStart,
                                    'due_date' => $taskDue,
                                ], JSON_UNESCAPED_UNICODE));
                                ?>
                                <tr>
                                    <td>
                                        <?php if ($task): ?>
                                            <span class="fw-semibold"><?= $taskTitle ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= $executor ? Html::encode($executor->fio . ' (' . $executor->email . ')') : '<span class="text-muted">—</span>' ?>
                                    </td>
                                    <td>
                                        <?= $requestedBy ? Html::encode($requestedBy->fio) : '<span class="text-muted">—</span>' ?>
                                    </td>
                                    <td>
                                        <?php if ($project): ?>
                                            <?= Html::encode($project->title) ?>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($task): ?>
                                            <button type="button" class="btn btn-outline-secondary btn-sm me-1 task-info-btn" data-task="<?= $taskData ?>">
                                                <i class="fas fa-info-circle"></i> Подробнее
                                            </button>
                                        <?php endif; ?>
                                        <?= Html::beginForm(['approve', 'id' => (string)$req->_id], 'post', ['class' => 'd-inline']) ?>
                                        <?= Html::submitButton('Одобрить', ['class' => 'btn btn-success btn-sm']) ?>
                                        <?= Html::endForm() ?>
                                        <?= Html::beginForm(['reject', 'id' => (string)$req->_id], 'post', ['class' => 'd-inline ms-1']) ?>
                                        <?= Html::submitButton('Отклонить', ['class' => 'btn btn-outline-danger btn-sm']) ?>
                                        <?= Html::endForm() ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

</div>

<!-- Модальное окно с информацией о задаче -->
<div class="modal fade" id="taskInfoModal" tabindex="-1" aria-labelledby="taskInfoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="taskInfoModalLabel">Информация о задаче</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body" id="taskInfoModalBody">
                <p class="text-muted">Загрузка...</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var modal = document.getElementById('taskInfoModal');
    var modalBody = document.getElementById('taskInfoModalBody');
    var modalTitle = document.getElementById('taskInfoModalLabel');
    if (!modal || !modalBody) return;
    document.querySelectorAll('.task-info-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var dataStr = this.getAttribute('data-task');
            if (!dataStr) return;
            var data;
            try {
                data = JSON.parse(dataStr);
            } catch (e) {
                modalBody.innerHTML = '<p class="text-danger">Ошибка данных</p>';
                return;
            }
            modalTitle.textContent = data.title || 'Информация о задаче';
            var html = '';
            html += '<div class="mb-3"><label class="text-muted small d-block">Название</label><div class="fw-semibold">' + (data.title || '—') + '</div></div>';
            html += '<div class="mb-3"><label class="text-muted small d-block">Описание</label><div class="task-info-description">' + (data.description || '—').replace(/\n/g, '<br>') + '</div></div>';
            html += '<div class="row mb-2"><div class="col-md-6"><label class="text-muted small d-block">Статус</label><div>' + (data.status || '—') + '</div></div>';
            html += '<div class="col-md-6"><label class="text-muted small d-block">Приоритет</label><div>' + (data.priority || '—') + '</div></div></div>';
            html += '<div class="row mb-2"><div class="col-md-6"><label class="text-muted small d-block">Проект</label><div>' + (data.project || '—') + '</div></div></div>';
            html += '<div class="row"><div class="col-md-6"><label class="text-muted small d-block">Дата начала</label><div>' + (data.start_date || '—') + '</div></div>';
            html += '<div class="col-md-6"><label class="text-muted small d-block">Срок выполнения</label><div>' + (data.due_date || '—') + '</div></div></div>';
            modalBody.innerHTML = html;
            var bsModal = new bootstrap.Modal(modal);
            bsModal.show();
        });
    });
});
</script>
