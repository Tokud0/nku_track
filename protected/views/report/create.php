<?php

use yii\helpers\Html;

/** @var yii\web\View $this */

$this->title = 'Создать отчёт';
$this->params['breadcrumbs'][] = ['label' => 'Отчёты', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$user = Yii::$app->user->identity;

// Mock: динамический шаблон отчёта (из ProjectSpec->report_template)
$reportTemplate = [
    'Достигнутые результаты' => 'Опишите достигнутые результаты за отчётный период',
    'Выполненные задачи' => 'Список выполненных задач',
    'Проблемы и риски' => 'Возникшие проблемы, риски и способы их решения',
    'План на следующий период' => 'Планируемые действия на следующий отчётный период',
];

// Mock: проекты для выбора
$mockProjects = [
    ['id' => '1', 'title' => 'Модернизация IT-инфраструктуры'],
    ['id' => '2', 'title' => 'Разработка мобильного приложения'],
    ['id' => '3', 'title' => 'Внедрение CRM системы'],
];
?>

<div class="report-create">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1">
                <i class="fas fa-plus-circle me-2"></i>
                <?= Html::encode($this->title) ?>
            </h1>
            <p class="text-muted mb-0">Заполните форму отчёта по проекту</p>
        </div>
        <?= Html::a(
            '<i class="fas fa-arrow-left me-2"></i>Назад',
            ['report/index'],
            ['class' => 'nku-btn nku-btn--secondary']
        ) ?>
    </div>

    <!-- Coming Soon Banner -->
    <div class="alert alert-info d-flex align-items-center mb-4" role="alert">
        <i class="fas fa-info-circle me-3" style="font-size: 1.5rem;"></i>
        <div>
            <strong>UI-прототип</strong>
            <div class="small">Форма демонстрирует динамические поля из report_template. Отправка данных будет доступна после интеграции с бэкендом.</div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Main Form -->
            <div class="nku-card mb-4">
                <div class="nku-card__header">
                    <h5 class="mb-0">Основная информация</h5>
                </div>
                <div class="nku-card__body">
                    <form>
                        <!-- Project Selection -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold required">
                                <i class="fas fa-project-diagram me-2"></i>
                                Проект
                            </label>
                            <select class="form-select" required disabled>
                                <option value="">Выберите проект</option>
                                <?php foreach ($mockProjects as $project): ?>
                                    <option value="<?= $project['id'] ?>"><?= Html::encode($project['title']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">Выберите проект, по которому создаётся отчёт</small>
                        </div>

                        <!-- Reporting Period -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold required">Начало периода</label>
                                <input type="date" class="form-control" required disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold required">Окончание периода</label>
                                <input type="date" class="form-control" required disabled>
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- Dynamic Fields from report_template -->
                        <h6 class="mb-3">
                            <i class="fas fa-list-ul me-2"></i>
                            Поля отчёта
                            <small class="text-muted">(динамически из шаблона)</small>
                        </h6>

                        <?php foreach ($reportTemplate as $fieldName => $fieldDescription): ?>
                            <div class="mb-4">
                                <label class="form-label fw-semibold required">
                                    <?= Html::encode($fieldName) ?>
                                </label>
                                <textarea class="form-control" 
                                          rows="4" 
                                          placeholder="<?= Html::encode($fieldDescription) ?>"
                                          required
                                          disabled></textarea>
                                <small class="form-text text-muted"><?= Html::encode($fieldDescription) ?></small>
                            </div>
                        <?php endforeach; ?>

                        <!-- Attachments -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-paperclip me-2"></i>
                                Приложения
                            </label>
                            <input type="file" class="form-control" multiple disabled>
                            <small class="form-text text-muted">Прикрепите файлы, документы, скриншоты (опционально)</small>
                        </div>

                        <!-- Actions -->
                        <div class="d-flex gap-2 justify-content-end">
                            <?= Html::a(
                                'Отмена',
                                ['report/index'],
                                ['class' => 'nku-btn nku-btn--secondary']
                            ) ?>
                            <button type="submit" class="nku-btn nku-btn--success" disabled>
                                <i class="fas fa-paper-plane me-2"></i>
                                Отправить отчёт
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar: Help & Info -->
        <div class="col-lg-4">
            <div class="nku-card mb-4">
                <div class="nku-card__header">
                    <h5 class="mb-0">
                        <i class="fas fa-question-circle me-2"></i>
                        Справка
                    </h5>
                </div>
                <div class="nku-card__body">
                    <h6 class="mb-2">Как заполнить отчёт?</h6>
                    <ol class="small mb-0">
                        <li class="mb-2">Выберите проект из списка</li>
                        <li class="mb-2">Укажите отчётный период</li>
                        <li class="mb-2">Заполните все обязательные поля</li>
                        <li class="mb-2">Прикрепите документы при необходимости</li>
                        <li>Нажмите "Отправить отчёт"</li>
                    </ol>
                </div>
            </div>

            <div class="nku-card">
                <div class="nku-card__header">
                    <h5 class="mb-0">
                        <i class="fas fa-lightbulb me-2"></i>
                        Советы
                    </h5>
                </div>
                <div class="nku-card__body">
                    <ul class="small mb-0">
                        <li class="mb-2">Будьте конкретны в описании результатов</li>
                        <li class="mb-2">Указывайте метрики и количественные показатели</li>
                        <li class="mb-2">Не забудьте упомянуть возникшие проблемы</li>
                        <li>Сохраняйте черновик перед отправкой</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.required::after {
    content: ' *';
    color: var(--nku-color-danger);
}
</style>

