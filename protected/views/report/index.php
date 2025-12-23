<?php

use yii\helpers\Html;

/** @var yii\web\View $this */

$this->title = 'Отчёты';
$this->params['breadcrumbs'][] = $this->title;

$user = Yii::$app->user->identity;

// Mock данные для демонстрации UI
$mockReports = [
    [
        'id' => '1',
        'project_title' => 'Модернизация IT-инфраструктуры',
        'executor_name' => 'Иванов Иван Иванович',
        'status' => 'sent',
        'created_at' => time() - 86400 * 2,
        'period' => '01.12.2024 - 15.12.2024',
    ],
    [
        'id' => '2',
        'project_title' => 'Разработка мобильного приложения',
        'executor_name' => 'Петрова Мария Сергеевна',
        'status' => 'accepted',
        'created_at' => time() - 86400 * 5,
        'period' => '15.11.2024 - 30.11.2024',
    ],
    [
        'id' => '3',
        'project_title' => 'Внедрение CRM системы',
        'executor_name' => 'Сидоров Петр Александрович',
        'status' => 'revision',
        'created_at' => time() - 86400 * 1,
        'period' => '10.12.2024 - 20.12.2024',
    ],
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

<div class="report-index">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1">
                <i class="fas fa-file-alt me-2"></i>
                <?= Html::encode($this->title) ?>
            </h1>
            <p class="text-muted mb-0">Управление отчётами по проектам</p>
        </div>
        <div class="d-flex gap-2">
            <?= Html::a(
                '<i class="fas fa-plus-circle me-2"></i>Создать отчёт',
                ['report/create'],
                ['class' => 'nku-btn nku-btn--success']
            ) ?>
        </div>
    </div>

    <!-- Coming Soon Banner -->
    <div class="alert alert-info d-flex align-items-center mb-4" role="alert">
        <i class="fas fa-info-circle me-3" style="font-size: 1.5rem;"></i>
        <div>
            <strong>Функционал в разработке</strong>
            <div class="small">Это UI-прототип. Интеграция с бэкендом будет добавлена в следующих версиях.</div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="nku-card mb-4">
        <div class="nku-card__body">
            <form class="nku-filter-bar">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">
                            <i class="fas fa-project-diagram me-1"></i>
                            Проект
                        </label>
                        <select class="form-select" disabled>
                            <option>Все проекты</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">
                            <i class="fas fa-tag me-1"></i>
                            Статус
                        </label>
                        <select class="form-select" disabled>
                            <option>Все статусы</option>
                            <option>Отправлен</option>
                            <option>Принят</option>
                            <option>На доработке</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">
                            <i class="fas fa-user me-1"></i>
                            Исполнитель
                        </label>
                        <select class="form-select" disabled>
                            <option>Все исполнители</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <button type="button" class="nku-btn nku-btn--primary w-100" disabled>
                            <i class="fas fa-search me-2"></i>Применить фильтры
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Reports List -->
    <div class="row">
        <?php foreach ($mockReports as $report): ?>
            <div class="col-lg-6 mb-4">
                <div class="nku-card nku-card--hoverable h-100">
                    <div class="nku-card__header">
                        <div class="d-flex justify-content-between align-items-start">
                            <h5 class="mb-0 flex-grow-1 me-2">
                                <i class="fas fa-file-alt me-2"></i>
                                Отчёт #<?= $report['id'] ?>
                            </h5>
                            <span class="nku-badge nku-badge--<?= $statusColors[$report['status']] ?>">
                                <?= $statusLabels[$report['status']] ?>
                            </span>
                        </div>
                    </div>
                    <div class="nku-card__body">
                        <div class="mb-3">
                            <label class="text-muted mb-1">Проект</label>
                            <div class="fw-semibold"><?= Html::encode($report['project_title']) ?></div>
                        </div>

                        <div class="mb-3">
                            <label class="text-muted mb-1">Исполнитель</label>
                            <div class="d-flex align-items-center">
                                <i class="fas fa-user-circle text-primary me-2" style="font-size: 1.5rem;"></i>
                                <span><?= Html::encode($report['executor_name']) ?></span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="text-muted mb-1">Отчётный период</label>
                            <div>
                                <i class="far fa-calendar me-1"></i>
                                <?= $report['period'] ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="text-muted mb-1">Дата создания</label>
                            <div>
                                <i class="far fa-clock me-1"></i>
                                <?= date('d.m.Y H:i', $report['created_at']) ?>
                            </div>
                        </div>
                    </div>
                    <div class="nku-card__footer">
                        <div class="d-flex gap-2">
                            <?= Html::a(
                                '<i class="fas fa-eye me-2"></i>Просмотр',
                                ['report/view', 'id' => $report['id']],
                                ['class' => 'nku-btn nku-btn--sm nku-btn--outline-primary flex-grow-1']
                            ) ?>
                            <?php if ($report['status'] === 'revision'): ?>
                                <?= Html::a(
                                    '<i class="fas fa-edit me-2"></i>Доработать',
                                    ['report/update', 'id' => $report['id']],
                                    ['class' => 'nku-btn nku-btn--sm nku-btn--outline-warning']
                                ) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Empty State (показать если нет отчётов) -->
    <!-- 
    <div class="nku-empty">
        <div class="nku-empty__icon">
            <i class="fas fa-file-alt"></i>
        </div>
        <div class="nku-empty__title">Отчётов пока нет</div>
        <div class="nku-empty__description">
            Отчёты будут появляться здесь после их создания исполнителями
        </div>
        <div class="nku-empty__action">
            <?= Html::a(
                '<i class="fas fa-plus-circle me-2"></i>Создать первый отчёт',
                ['report/create'],
                ['class' => 'nku-btn nku-btn--primary']
            ) ?>
        </div>
    </div>
    -->
</div>

