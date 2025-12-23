<?php

use yii\helpers\Html;

/** @var yii\web\View $this */

$this->title = 'Уведомления';
$this->params['breadcrumbs'][] = $this->title;

$user = Yii::$app->user->identity;

// Mock данные уведомлений
$mockNotifications = [
    [
        'id' => '1',
        'type' => 'new_report',
        'title' => 'Новый отчёт по проекту',
        'message' => 'Иванов И.И. отправил отчёт по проекту "Модернизация IT-инфраструктуры"',
        'created_at' => time() - 3600,
        'is_read' => false,
        'link' => '/report/view?id=1',
    ],
    [
        'id' => '2',
        'type' => 'deadline',
        'title' => 'Приближается срок сдачи',
        'message' => 'Срок сдачи задачи "Настройка сервера" истекает через 2 дня',
        'created_at' => time() - 7200,
        'is_read' => false,
        'link' => '/task/view?id=15',
    ],
    [
        'id' => '3',
        'type' => 'report_status',
        'title' => 'Отчёт принят',
        'message' => 'Ваш отчёт по проекту "Разработка мобильного приложения" был принят',
        'created_at' => time() - 86400,
        'is_read' => true,
        'link' => '/report/view?id=2',
    ],
    [
        'id' => '4',
        'type' => 'assign',
        'title' => 'Назначена новая задача',
        'message' => 'Вам назначена задача "Тестирование API" в проекте "Внедрение CRM"',
        'created_at' => time() - 86400 * 2,
        'is_read' => true,
        'link' => '/task/view?id=23',
    ],
];

$typeIcons = [
    'new_report' => ['icon' => 'fa-file-alt', 'color' => 'primary'],
    'deadline' => ['icon' => 'fa-clock', 'color' => 'warning'],
    'report_status' => ['icon' => 'fa-check-circle', 'color' => 'success'],
    'assign' => ['icon' => 'fa-user-tag', 'color' => 'info'],
];

$unreadCount = count(array_filter($mockNotifications, fn($n) => !$n['is_read']));
?>

<div class="notification-index">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1">
                <i class="fas fa-bell me-2"></i>
                <?= Html::encode($this->title) ?>
                <?php if ($unreadCount > 0): ?>
                    <span class="nku-badge nku-badge--danger ms-2"><?= $unreadCount ?></span>
                <?php endif; ?>
            </h1>
            <p class="text-muted mb-0">Все уведомления системы</p>
        </div>
        <div class="d-flex gap-2">
            <button class="nku-btn nku-btn--outline-primary" disabled>
                <i class="fas fa-check-double me-2"></i>
                Отметить все как прочитанные
            </button>
        </div>
    </div>

    <!-- Coming Soon Banner -->
    <div class="alert alert-info d-flex align-items-center mb-4" role="alert">
        <i class="fas fa-info-circle me-3" style="font-size: 1.5rem;"></i>
        <div>
            <strong>Функционал в разработке</strong>
            <div class="small">UI-прототип ленты уведомлений. Real-time уведомления будут доступны после интеграции с бэкендом.</div>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="nku-card mb-4">
        <div class="nku-card__body py-2">
            <ul class="nav nav-pills">
                <li class="nav-item">
                    <a class="nav-link active" href="#" onclick="return false;">
                        <i class="fas fa-inbox me-2"></i>
                        Все
                        <span class="badge bg-secondary ms-2"><?= count($mockNotifications) ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" onclick="return false;">
                        <i class="fas fa-envelope me-2"></i>
                        Непрочитанные
                        <span class="badge bg-danger ms-2"><?= $unreadCount ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" onclick="return false;">
                        <i class="fas fa-file-alt me-2"></i>
                        Отчёты
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" onclick="return false;">
                        <i class="fas fa-tasks me-2"></i>
                        Задачи
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" onclick="return false;">
                        <i class="fas fa-clock me-2"></i>
                        Дедлайны
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Notifications List -->
    <div class="nku-notifications-list">
        <?php if (empty($mockNotifications)): ?>
            <div class="nku-empty">
                <div class="nku-empty__icon">
                    <i class="fas fa-bell-slash"></i>
                </div>
                <div class="nku-empty__title">Нет уведомлений</div>
                <div class="nku-empty__description">
                    Все уведомления будут появляться здесь
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($mockNotifications as $notification): ?>
                <?php
                $typeInfo = $typeIcons[$notification['type']] ?? ['icon' => 'fa-info', 'color' => 'secondary'];
                $isUnread = !$notification['is_read'];
                ?>
                <div class="nku-notification-item <?= $isUnread ? 'nku-notification-item--unread' : '' ?>">
                    <div class="nku-notification-item__icon nku-notification-item__icon--<?= $typeInfo['color'] ?>">
                        <i class="fas <?= $typeInfo['icon'] ?>"></i>
                    </div>
                    <div class="nku-notification-item__content">
                        <div class="nku-notification-item__header">
                            <h6 class="nku-notification-item__title">
                                <?= Html::encode($notification['title']) ?>
                                <?php if ($isUnread): ?>
                                    <span class="nku-badge nku-badge--xs nku-badge--danger ms-2">Новое</span>
                                <?php endif; ?>
                            </h6>
                            <span class="nku-notification-item__time">
                                <?php
                                $diff = time() - $notification['created_at'];
                                if ($diff < 3600) {
                                    echo floor($diff / 60) . ' мин назад';
                                } elseif ($diff < 86400) {
                                    echo floor($diff / 3600) . ' ч назад';
                                } else {
                                    echo floor($diff / 86400) . ' дн назад';
                                }
                                ?>
                            </span>
                        </div>
                        <p class="nku-notification-item__message">
                            <?= Html::encode($notification['message']) ?>
                        </p>
                        <div class="nku-notification-item__actions">
                            <?= Html::a(
                                '<i class="fas fa-external-link-alt me-1"></i>Перейти',
                                $notification['link'],
                                ['class' => 'nku-btn nku-btn--xs nku-btn--outline-primary']
                            ) ?>
                            <?php if ($isUnread): ?>
                                <button class="nku-btn nku-btn--xs nku-btn--outline-secondary" disabled>
                                    <i class="fas fa-check me-1"></i>Прочитано
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<style>
/* Notifications List */
.nku-notifications-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.nku-notification-item {
    display: flex;
    gap: 1rem;
    padding: 1.25rem;
    background: white;
    border: 1px solid var(--nku-color-border);
    border-radius: var(--nku-border-radius);
    transition: all 0.2s ease;
}

.nku-notification-item:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    transform: translateY(-2px);
}

.nku-notification-item--unread {
    background: var(--nku-color-primary-light);
    border-left: 4px solid var(--nku-color-primary);
}

.nku-notification-item__icon {
    flex-shrink: 0;
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    color: white;
}

.nku-notification-item__icon--primary { background: var(--nku-color-primary); }
.nku-notification-item__icon--success { background: var(--nku-color-success); }
.nku-notification-item__icon--warning { background: var(--nku-color-warning); }
.nku-notification-item__icon--info { background: var(--nku-color-info); }
.nku-notification-item__icon--danger { background: var(--nku-color-danger); }

.nku-notification-item__content {
    flex-grow: 1;
}

.nku-notification-item__header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 0.5rem;
}

.nku-notification-item__title {
    font-size: 1rem;
    font-weight: 600;
    margin: 0;
}

.nku-notification-item__time {
    font-size: 0.75rem;
    color: var(--nku-color-text-secondary);
    white-space: nowrap;
}

.nku-notification-item__message {
    color: var(--nku-color-text-secondary);
    margin-bottom: 0.75rem;
    line-height: 1.5;
}

.nku-notification-item__actions {
    display: flex;
    gap: 0.5rem;
}

/* Nav pills customization */
.nav-pills .nav-link {
    color: var(--nku-color-text-secondary);
    border-radius: var(--nku-border-radius);
}

.nav-pills .nav-link:hover {
    background: var(--nku-color-background);
}

.nav-pills .nav-link.active {
    background: var(--nku-color-primary);
}
</style>

