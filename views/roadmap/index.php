<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\models\User;

/** @var yii\web\View $this */
/** @var app\models\Department $department */
/** @var app\models\Roadmap|null $roadmap */

$this->title = 'Дорожная карта подразделения';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="roadmap-index">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= Html::encode($this->title) ?></h1>
        <?php if (!$roadmap): ?>
            <?= Html::a('Создать дорожную карту', ['create'], ['class' => 'btn btn-success']) ?>
        <?php else: ?>
            <?= Html::a('Редактировать дорожную карту', ['update', 'id' => (string)$roadmap->_id], ['class' => 'btn btn-primary']) ?>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Подразделение: <?= Html::encode($department->name) ?></h5>
        </div>
        <div class="card-body">
            <?php if (!$roadmap): ?>
                <div class="alert alert-info">
                    <p>Для вашего подразделения еще не создана дорожная карта.</p>
                    <p>Дорожная карта позволяет определить этапы развития подразделения с указанием временных рамок, описаний и целей каждого этапа.</p>
                    <?= Html::a('Создать дорожную карту', ['create'], ['class' => 'btn btn-success']) ?>
                </div>
            <?php else: ?>
                <?php
                $stages = $roadmap->stages;
                if (empty($stages)):
                ?>
                    <div class="alert alert-warning">
                        <p>Дорожная карта создана, но этапы еще не добавлены.</p>
                        <?= Html::a('Добавить этапы', ['update', 'id' => (string)$roadmap->_id], ['class' => 'btn btn-primary']) ?>
                    </div>
                <?php else: ?>
                    <div class="roadmap-timeline">
                        <?php foreach ($stages as $stage): ?>
                            <div class="card mb-4 stage-card">
                                <div class="card-header bg-primary text-white">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">
                                            <?= Html::encode($stage->name) ?>
                                            <?php if (!empty($stage->is_completed)): ?>
                                                <span class="badge bg-success ms-2">Завершен</span>
                                            <?php endif; ?>
                                        </h5>
                                        <div class="text-end">
                                            <span class="badge bg-light text-dark">
                                                <?= $stage->start_month ?> - <?= $stage->end_month ?> месяцев
                                            </span>
                                            <?php if ($stage->start_date instanceof \MongoDB\BSON\UTCDateTime || $stage->end_date instanceof \MongoDB\BSON\UTCDateTime): ?>
                                                <div class="mt-1">
                                                    <small class="text-white">
                                                        <?php if ($stage->start_date instanceof \MongoDB\BSON\UTCDateTime): ?>
                                                            с <?= date('d.m.Y', $stage->start_date->toDateTime()->getTimestamp()) ?>
                                                        <?php endif; ?>
                                                        <?php if ($stage->end_date instanceof \MongoDB\BSON\UTCDateTime): ?>
                                                            по <?= date('d.m.Y', $stage->end_date->toDateTime()->getTimestamp()) ?>
                                                        <?php endif; ?>
                                                    </small>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($stage->is_completed) && !empty($stage->completion_format)): ?>
                                        <div class="alert alert-success mb-3">
                                            <strong><i class="fas fa-check-circle me-2"></i>Этап завершен</strong>
                                            <p class="mb-0 mt-2"><strong>Формат завершения:</strong> <?= nl2br(Html::encode($stage->completion_format)) ?></p>
                                            <?php if ($stage->completed_at instanceof \MongoDB\BSON\UTCDateTime): ?>
                                                <small class="text-muted d-block mt-1">
                                                    <i class="far fa-calendar me-1"></i>
                                                    Дата завершения: <?= date('d.m.Y', $stage->completed_at->toDateTime()->getTimestamp()) ?>
                                                </small>
                                            <?php endif; ?>
                                            <?php if (!empty($stage->completion_file_name) && !empty($stage->completion_file_data)): ?>
                                                <div class="mt-2">
                                                    <strong>Файл завершения:</strong>
                                                    <?= Html::a(
                                                        Html::encode($stage->completion_file_name),
                                                        ['download-completion', 'id' => (string)$stage->_id],
                                                        [
                                                            'class' => 'ms-1 text-decoration-underline',
                                                            'target' => '_blank'
                                                        ]
                                                    ) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($stage->description): ?>
                                        <p class="card-text"><strong>Описание:</strong> <?= nl2br(Html::encode($stage->description)) ?></p>
                                    <?php endif; ?>
                                    
                                    <?php
                                    $goals = $stage->goals;
                                    if (!empty($goals)):
                                    ?>
                                        <h6 class="mt-3">Цели этапа:</h6>
                                        <ul class="list-group">
                                            <?php foreach ($goals as $goal): ?>
                                                <li class="list-group-item">
                                                    <strong><?= Html::encode($goal->title) ?></strong>
                                                    <?php if ($goal->description): ?>
                                                        <br><small class="text-muted"><?= nl2br(Html::encode($goal->description)) ?></small>
                                                    <?php endif; ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>

                                    <?php
                                    $currentUser = Yii::$app->user->identity;
                                    $canComplete = $currentUser && $currentUser->role !== User::ROLE_RECTOR;
                                    ?>

                                    <?php if ($canComplete && empty($stage->is_completed)): ?>
                                        <hr>
                                        <button type="button"
                                                class="btn btn-success w-100 stage-complete-open-modal"
                                                data-stage-id="<?= (string)$stage->_id ?>">
                                            Завершить этап и добавить отчет
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-3">
                        <?= Html::a('Редактировать дорожную карту', ['update', 'id' => (string)$roadmap->_id], ['class' => 'btn btn-primary']) ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

<?php
// Модальное окно для завершения этапа (один инстанс, наполняем через JS)
?>
<div class="modal fade" id="stage-complete-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Завершение этапа</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body">
                <form id="stage-complete-form" method="post" enctype="multipart/form-data"
                      action="<?= Url::to(['roadmap/complete-stage']) ?>">
                    <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->getCsrfToken()) ?>
                    <input type="hidden" name="id" id="stage-complete-id">

                    <div class="mb-3">
                        <label class="form-label">Формат завершения (обязательно)</label>
                        <textarea name="completion_format"
                                  class="form-control"
                                  rows="4"
                                  required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            Документ (опционально, PDF / Word / PNG / JPG)
                        </label>
                        <input type="file"
                               name="completion_file"
                               class="form-control"
                               accept=".pdf,.doc,.docx,.png,.jpg,.jpeg">
                        <div class="form-text">
                            Прикрепите отчет, презентацию или другой файл, подтверждающий завершение этапа.
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-success" id="stage-complete-submit">
                    Сохранить завершение этапа
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Инициализация Bootstrap modal, если он есть
    var modalElement = document.getElementById('stage-complete-modal');
    var modalInstance = null;
    if (modalElement && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        modalInstance = new bootstrap.Modal(modalElement);
    }

    // Открытие модалки из кнопок на этапах
    document.querySelectorAll('.stage-complete-open-modal').forEach(function (button) {
        button.addEventListener('click', function () {
            var stageId = this.getAttribute('data-stage-id');
            var form = document.getElementById('stage-complete-form');
            var idInput = document.getElementById('stage-complete-id');

            if (idInput) {
                idInput.value = stageId;
            }

            if (form) {
                form.reset();
            }

            if (modalInstance) {
                modalInstance.show();
            } else if (modalElement) {
                // Фоллбек, если Bootstrap modal недоступен
                modalElement.style.display = 'block';
                modalElement.classList.add('show');
            }
        });
    });

    // Сабмит формы из кнопки в футере модалки
    var submitButton = document.getElementById('stage-complete-submit');
    if (submitButton) {
        submitButton.addEventListener('click', function () {
            var form = document.getElementById('stage-complete-form');
            if (!form) return;

            var formData = new FormData(form);
            var actionUrl = form.getAttribute('action');

            fetch(actionUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {
                    if (data && data.success) {
                        window.location.reload();
                    } else {
                        alert(data && data.message ? data.message : 'Ошибка при завершении этапа.');
                    }
                })
                .catch(function () {
                    alert('Ошибка при завершении этапа.');
                });
        });
    }
});
</script>

