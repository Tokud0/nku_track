<?php

use yii\helpers\Html;

/** @var yii\web\View $this */

$this->title = 'Отчёт #1';
$this->params['breadcrumbs'][] = ['label' => 'Отчёты', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$user = Yii::$app->user->identity;

// Mock данные отчёта
$report = [
    'id' => '1',
    'project_title' => 'Модернизация IT-инфраструктуры',
    'executor_name' => 'Иванов Иван Иванович',
    'executor_email' => 'ivanov@example.com',
    'status' => 'revision', // sent, accepted, revision
    'period_start' => '01.12.2024',
    'period_end' => '15.12.2024',
    'created_at' => time() - 86400 * 2,
    'fields' => [
        'Достигнутые результаты' => 'Проведена модернизация серверного оборудования, обновлено ПО на 15 рабочих станциях, настроена система резервного копирования.',
        'Выполненные задачи' => '1. Закупка и установка нового сервера\n2. Обновление операционных систем\n3. Настройка сетевой инфраструктуры\n4. Тестирование системы безопасности',
        'Проблемы и риски' => 'Обнаружена несовместимость старого оборудования с новым ПО. Требуется дополнительный бюджет на замену 3 единиц техники.',
        'План на следующий период' => 'Завершить миграцию данных, провести обучение сотрудников, подготовить документацию.',
    ],
    'attachments' => [
        'server_config.pdf',
        'network_diagram.png',
        'backup_report.xlsx',
    ],
    'manager_comment' => 'Необходимо более детально описать возникшие проблемы и предоставить смету на дополнительное оборудование. Также прошу добавить скриншоты работающей системы.',
];

$statusLabels = [
    'sent' => 'Отправлен',
    'accepted' => 'Принят',
    'revision' => 'На доработке',
];

$statusColors = [
    'sent' => 'primary',
    'accepted' => 'success',
    'revision' => 'warning',
];
?>

<div class="report-view">
    <!-- Header -->
    <div class="nku-card mb-4">
        <div class="nku-card__body">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <h1 class="mb-0"><?= Html::encode($this->title) ?></h1>
                        <span class="nku-badge nku-badge--lg nku-badge--<?= $statusColors[$report['status']] ?>">
                            <i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i>
                            <?= $statusLabels[$report['status']] ?>
                        </span>
                    </div>
                    <p class="text-muted mb-0">
                        <i class="fas fa-project-diagram me-2"></i>
                        <?= Html::encode($report['project_title']) ?>
                    </p>
                </div>
                <div class="text-end">
                    <?= Html::a(
                        '<i class="fas fa-arrow-left me-2"></i>К списку',
                        ['report/index'],
                        ['class' => 'nku-btn nku-btn--secondary mb-2']
                    ) ?>
                    <?php if ($report['status'] === 'revision'): ?>
                        <div class="d-flex gap-2">
                            <?= Html::a(
                                '<i class="fas fa-edit me-2"></i>Доработать',
                                ['report/update', 'id' => $report['id']],
                                ['class' => 'nku-btn nku-btn--warning']
                            ) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Report Period -->
            <div class="d-flex gap-4">
                <div>
                    <label class="text-muted mb-1">Отчётный период</label>
                    <div class="fw-semibold">
                        <i class="far fa-calendar me-1"></i>
                        <?= $report['period_start'] ?> — <?= $report['period_end'] ?>
                    </div>
                </div>
                <div>
                    <label class="text-muted mb-1">Дата создания</label>
                    <div class="fw-semibold">
                        <i class="far fa-clock me-1"></i>
                        <?= date('d.m.Y H:i', $report['created_at']) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Coming Soon Banner -->
    <div class="alert alert-info d-flex align-items-center mb-4" role="alert">
        <i class="fas fa-info-circle me-3" style="font-size: 1.5rem;"></i>
        <div>
            <strong>UI-прототип</strong>
            <div class="small">Отображение данных из report_template. Функционал смены статуса будет добавлен после интеграции.</div>
        </div>
    </div>

    <div class="row">
        <!-- Left Column: Report Content -->
        <div class="col-lg-8 mb-4">
            <!-- Executor Info -->
            <div class="nku-card mb-4">
                <div class="nku-card__header">
                    <h5 class="mb-0">
                        <i class="fas fa-user me-2"></i>
                        Исполнитель
                    </h5>
                </div>
                <div class="nku-card__body">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-user-circle text-primary me-3" style="font-size: 3rem;"></i>
                        <div>
                            <div class="fw-semibold fs-5"><?= Html::encode($report['executor_name']) ?></div>
                            <small class="text-muted"><?= Html::encode($report['executor_email']) ?></small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Report Fields -->
            <div class="nku-card mb-4">
                <div class="nku-card__header">
                    <h5 class="mb-0">
                        <i class="fas fa-file-alt me-2"></i>
                        Содержание отчёта
                    </h5>
                </div>
                <div class="nku-card__body">
                    <?php foreach ($report['fields'] as $fieldName => $fieldValue): ?>
                        <div class="report-field mb-4">
                            <h6 class="text-primary mb-2">
                                <i class="fas fa-caret-right me-2"></i>
                                <?= Html::encode($fieldName) ?>
                            </h6>
                            <div class="p-3 bg-light rounded">
                                <?= nl2br(Html::encode($fieldValue)) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Attachments -->
            <?php if (!empty($report['attachments'])): ?>
                <div class="nku-card">
                    <div class="nku-card__header">
                        <h5 class="mb-0">
                            <i class="fas fa-paperclip me-2"></i>
                            Приложения
                            <span class="nku-badge nku-badge--secondary ms-2"><?= count($report['attachments']) ?></span>
                        </h5>
                    </div>
                    <div class="nku-card__body">
                        <div class="list-group">
                            <?php foreach ($report['attachments'] as $index => $attachment): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-file text-primary me-3" style="font-size: 1.5rem;"></i>
                                        <div>
                                            <div class="fw-semibold"><?= Html::encode($attachment) ?></div>
                                            <small class="text-muted">Добавлен <?= date('d.m.Y H:i', $report['created_at']) ?></small>
                                        </div>
                                    </div>
                                    <button class="nku-btn nku-btn--xs nku-btn--outline-primary" disabled>
                                        <i class="fas fa-download"></i>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right Column: Status & Manager Feedback -->
        <div class="col-lg-4">
            <!-- Status Info -->
            <div class="nku-card mb-4">
                <div class="nku-card__header">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Статус
                    </h5>
                </div>
                <div class="nku-card__body">
                    <div class="text-center mb-3">
                        <span class="nku-badge nku-badge--lg nku-badge--<?= $statusColors[$report['status']] ?>">
                            <?= $statusLabels[$report['status']] ?>
                        </span>
                    </div>

                    <?php if ($report['status'] === 'sent'): ?>
                        <div class="alert alert-primary mb-0">
                            <i class="fas fa-paper-plane me-2"></i>
                            Отчёт отправлен и ожидает проверки руководителем
                        </div>
                    <?php elseif ($report['status'] === 'accepted'): ?>
                        <div class="alert alert-success mb-0">
                            <i class="fas fa-check-circle me-2"></i>
                            Отчёт принят руководителем без замечаний
                        </div>
                    <?php elseif ($report['status'] === 'revision'): ?>
                        <div class="alert alert-warning mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Требуется доработка. См. комментарий руководителя ниже
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Manager Comment (if status = revision) -->
            <?php if ($report['status'] === 'revision' && !empty($report['manager_comment'])): ?>
                <div class="nku-card mb-4">
                    <div class="nku-card__header bg-warning bg-opacity-10">
                        <h5 class="mb-0 text-warning">
                            <i class="fas fa-comment-dots me-2"></i>
                            Комментарий руководителя
                        </h5>
                    </div>
                    <div class="nku-card__body">
                        <div class="nku-comment">
                            <div class="nku-comment__avatar">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <div class="nku-comment__content">
                                <div class="nku-comment__header">
                                    <span class="nku-comment__author">Руководитель проекта</span>
                                    <span class="nku-comment__time">
                                        <i class="far fa-clock me-1"></i>
                                        <?= date('d.m.Y H:i', $report['created_at'] + 3600) ?>
                                    </span>
                                </div>
                                <div class="nku-comment__text">
                                    <?= nl2br(Html::encode($report['manager_comment'])) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Timeline -->
            <div class="nku-card">
                <div class="nku-card__header">
                    <h5 class="mb-0">
                        <i class="fas fa-history me-2"></i>
                        История
                    </h5>
                </div>
                <div class="nku-card__body">
                    <div class="nku-timeline">
                        <div class="nku-timeline__item">
                            <div class="nku-timeline__icon nku-timeline__icon--success">
                                <i class="fas fa-plus"></i>
                            </div>
                            <div class="nku-timeline__content">
                                <div class="nku-timeline__title">Создан</div>
                                <div class="nku-timeline__time">
                                    <?= date('d.m.Y H:i', $report['created_at']) ?>
                                </div>
                            </div>
                        </div>

                        <div class="nku-timeline__item">
                            <div class="nku-timeline__icon nku-timeline__icon--primary">
                                <i class="fas fa-paper-plane"></i>
                            </div>
                            <div class="nku-timeline__content">
                                <div class="nku-timeline__title">Отправлен</div>
                                <div class="nku-timeline__time">
                                    <?= date('d.m.Y H:i', $report['created_at'] + 300) ?>
                                </div>
                            </div>
                        </div>

                        <?php if ($report['status'] === 'revision'): ?>
                            <div class="nku-timeline__item">
                                <div class="nku-timeline__icon nku-timeline__icon--warning">
                                    <i class="fas fa-redo"></i>
                                </div>
                                <div class="nku-timeline__content">
                                    <div class="nku-timeline__title">Отправлен на доработку</div>
                                    <div class="nku-timeline__time">
                                        <?= date('d.m.Y H:i', $report['created_at'] + 3600) ?>
                                    </div>
                                </div>
                            </div>
                        <?php elseif ($report['status'] === 'accepted'): ?>
                            <div class="nku-timeline__item">
                                <div class="nku-timeline__icon nku-timeline__icon--success">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="nku-timeline__content">
                                    <div class="nku-timeline__title">Принят</div>
                                    <div class="nku-timeline__time">
                                        <?= date('d.m.Y H:i', $report['created_at'] + 7200) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.report-field h6 {
    font-weight: 600;
}

.report-field .bg-light {
    line-height: 1.7;
}
</style>

