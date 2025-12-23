<?php
/** @var yii\web\View $this */
/** @var \app\models\User|null $user */

use yii\bootstrap5\Html;
use yii\helpers\Url;

$isGuest = Yii::$app->user->isGuest;
$user = $user ?? ($isGuest ? null : Yii::$app->user->identity);

$route = Yii::$app->controller->route; // e.g. site/index
$isActive = function (string $needle) use ($route): bool {
    return str_starts_with($route, $needle);
};

$brandUrl = Yii::$app->homeUrl;
?>

<aside class="nku-sidebar" aria-label="Sidebar navigation">
    <a class="nku-sidebar__brand" href="<?= Html::encode($brandUrl) ?>">
        <span aria-hidden="true"><i class="fas fa-tasks"></i></span>
        <span>
            <div class="nku-sidebar__brand-title"><?= Html::encode(Yii::$app->name) ?></div>
            <div class="nku-sidebar__brand-subtitle">NKU Track</div>
        </span>
    </a>

    <nav class="nku-nav">
        <?php if ($isGuest): ?>
            <a class="nku-nav__item <?= $isActive('site/index') ? 'is-active' : '' ?>" href="<?= Html::encode(Url::to(['/site/index'])) ?>">
                <i class="fas fa-home" aria-hidden="true"></i> <span>Главная</span>
            </a>
            <a class="nku-nav__item <?= $isActive('site/about') ? 'is-active' : '' ?>" href="<?= Html::encode(Url::to(['/site/about'])) ?>">
                <i class="fas fa-circle-info" aria-hidden="true"></i> <span>О нас</span>
            </a>
            <a class="nku-nav__item <?= $isActive('site/contact') ? 'is-active' : '' ?>" href="<?= Html::encode(Url::to(['/site/contact'])) ?>">
                <i class="fas fa-envelope" aria-hidden="true"></i> <span>Контакты</span>
            </a>

            <div class="nku-nav__section">Аккаунт</div>
            <a class="nku-nav__item <?= $isActive('site/login') ? 'is-active' : '' ?>" href="<?= Html::encode(Url::to(['/site/login'])) ?>">
                <i class="fas fa-right-to-bracket" aria-hidden="true"></i> <span>Вход</span>
            </a>
            <a class="nku-nav__item <?= $isActive('site/signup') ? 'is-active' : '' ?>" href="<?= Html::encode(Url::to(['/site/signup'])) ?>">
                <i class="fas fa-user-plus" aria-hidden="true"></i> <span>Регистрация</span>
            </a>
        <?php else: ?>
            <div class="nku-nav__section">Навигация</div>
            <a class="nku-nav__item <?= $isActive('site/index') ? 'is-active' : '' ?>" href="<?= Html::encode(Url::to(['/site/index'])) ?>">
                <i class="fas fa-house" aria-hidden="true"></i> <span>Главная</span>
            </a>
            <a class="nku-nav__item <?= $isActive('project') ? 'is-active' : '' ?>" href="<?= Html::encode(Url::to(['/project/index'])) ?>">
                <i class="fas fa-project-diagram" aria-hidden="true"></i> <span>Проекты</span>
            </a>

            <?php if ($user && in_array($user->role, [\app\models\User::ROLE_RECTOR, \app\models\User::ROLE_TOP_MANAGER], true)): ?>
                <a class="nku-nav__item <?= $isActive('roadmap') ? 'is-active' : '' ?>" href="<?= Html::encode(Url::to(['/roadmap/index'])) ?>">
                    <i class="fas fa-road" aria-hidden="true"></i> <span>Дорожная карта</span>
                </a>
            <?php endif; ?>

            <?php
            // Feature flags для планируемых функций
            $FEATURE_REPORTS_UI = false; // true для включения UI отчётов
            $FEATURE_NOTIFICATIONS_UI = false; // true для включения UI уведомлений
            ?>

            <?php if ($FEATURE_REPORTS_UI): ?>
                <a class="nku-nav__item <?= $isActive('report') ? 'is-active' : '' ?>" href="<?= Html::encode(Url::to(['/report/index'])) ?>">
                    <i class="fas fa-file-alt" aria-hidden="true"></i> <span>Отчёты</span>
                </a>
            <?php else: ?>
                <div class="nku-nav__item nku-nav__item--disabled" title="Функционал в разработке">
                    <i class="fas fa-file-alt" aria-hidden="true"></i> 
                    <span>Отчёты</span>
                    <span class="nku-badge nku-badge--xs nku-badge--warning ms-auto">Soon</span>
                </div>
            <?php endif; ?>

            <?php if ($FEATURE_NOTIFICATIONS_UI): ?>
                <a class="nku-nav__item <?= $isActive('notification') ? 'is-active' : '' ?>" href="<?= Html::encode(Url::to(['/notification/index'])) ?>">
                    <i class="fas fa-bell" aria-hidden="true"></i> <span>Уведомления</span>
                </a>
            <?php else: ?>
                <div class="nku-nav__item nku-nav__item--disabled" title="Функционал в разработке">
                    <i class="fas fa-bell" aria-hidden="true"></i> 
                    <span>Уведомления</span>
                    <span class="nku-badge nku-badge--xs nku-badge--warning ms-auto">Soon</span>
                </div>
            <?php endif; ?>

            <?php if ($user && $user->role === \app\models\User::ROLE_ADMIN): ?>
                <div class="nku-nav__section">Администрирование</div>
                <a class="nku-nav__item <?= $isActive('admin') ? 'is-active' : '' ?>" href="<?= Html::encode(Url::to(['/admin'])) ?>">
                    <i class="fas fa-gear" aria-hidden="true"></i> <span>Админка</span>
                </a>
            <?php endif; ?>

            <div class="nku-nav__section">Аккаунт</div>
            <a class="nku-nav__item" href="<?= Html::encode(Url::to(['/admin/user/view', 'id' => (string)$user->_id])) ?>">
                <i class="fas fa-user-circle" aria-hidden="true"></i> <span>Профиль</span>
            </a>
            <?= Html::beginForm(['/site/logout'], 'post', ['class' => 'd-grid']) ?>
                <button type="submit" class="nku-nav__item" style="border: 0; background: transparent; text-align: left;">
                    <i class="fas fa-right-from-bracket" aria-hidden="true"></i> <span>Выход</span>
                </button>
            <?= Html::endForm() ?>
        <?php endif; ?>
    </nav>
</aside>


