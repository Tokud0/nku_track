<?php
/** Yii1 partial: Sidebar */

$isGuest = Yii::app()->user->isGuest;

// Defensive role detection (depends on your Yii1 auth implementation)
$role = null;
if (!$isGuest) {
    if (method_exists(Yii::app()->user, 'getState')) {
        $role = Yii::app()->user->getState('role');
    } elseif (property_exists(Yii::app()->user, 'role')) {
        $role = Yii::app()->user->role;
    }
}

$route = Yii::app()->controller->route; // e.g. site/index
$isActive = function ($needle) use ($route) {
    return strpos($route, $needle) === 0;
};
?>

<aside class="nku-sidebar" aria-label="Sidebar navigation">
    <a class="nku-sidebar__brand" href="<?php echo CHtml::encode(Yii::app()->homeUrl); ?>">
        <span aria-hidden="true"><i class="fas fa-tasks"></i></span>
        <span>
            <div class="nku-sidebar__brand-title"><?php echo CHtml::encode(Yii::app()->name); ?></div>
            <div class="nku-sidebar__brand-subtitle">NKU Track</div>
        </span>
    </a>

    <nav class="nku-nav">
        <?php if ($isGuest): ?>
            <a class="nku-nav__item <?php echo $isActive('site/index') ? 'is-active' : ''; ?>" href="<?php echo CHtml::encode(Yii::app()->createUrl('/site/index')); ?>">
                <i class="fas fa-home" aria-hidden="true"></i> <span>Главная</span>
            </a>
            <a class="nku-nav__item <?php echo $isActive('site/about') ? 'is-active' : ''; ?>" href="<?php echo CHtml::encode(Yii::app()->createUrl('/site/about')); ?>">
                <i class="fas fa-circle-info" aria-hidden="true"></i> <span>О нас</span>
            </a>
            <a class="nku-nav__item <?php echo $isActive('site/contact') ? 'is-active' : ''; ?>" href="<?php echo CHtml::encode(Yii::app()->createUrl('/site/contact')); ?>">
                <i class="fas fa-envelope" aria-hidden="true"></i> <span>Контакты</span>
            </a>

            <div class="nku-nav__section">Аккаунт</div>
            <a class="nku-nav__item <?php echo $isActive('site/login') ? 'is-active' : ''; ?>" href="<?php echo CHtml::encode(Yii::app()->createUrl('/site/login')); ?>">
                <i class="fas fa-right-to-bracket" aria-hidden="true"></i> <span>Вход</span>
            </a>
            <a class="nku-nav__item <?php echo $isActive('site/signup') ? 'is-active' : ''; ?>" href="<?php echo CHtml::encode(Yii::app()->createUrl('/site/signup')); ?>">
                <i class="fas fa-user-plus" aria-hidden="true"></i> <span>Регистрация</span>
            </a>
        <?php else: ?>
            <div class="nku-nav__section">Навигация</div>
            <a class="nku-nav__item <?php echo $isActive('site/index') ? 'is-active' : ''; ?>" href="<?php echo CHtml::encode(Yii::app()->createUrl('/site/index')); ?>">
                <i class="fas fa-house" aria-hidden="true"></i> <span>Главная</span>
            </a>
            <a class="nku-nav__item <?php echo $isActive('project') ? 'is-active' : ''; ?>" href="<?php echo CHtml::encode(Yii::app()->createUrl('/project/index')); ?>">
                <i class="fas fa-project-diagram" aria-hidden="true"></i> <span>Проекты</span>
            </a>

            <?php if (in_array($role, array('rector', 'top_manager'), true)): ?>
                <a class="nku-nav__item <?php echo $isActive('roadmap') ? 'is-active' : ''; ?>" href="<?php echo CHtml::encode(Yii::app()->createUrl('/roadmap/index')); ?>">
                    <i class="fas fa-road" aria-hidden="true"></i> <span>Дорожная карта</span>
                </a>
            <?php endif; ?>

            <?php
            // Feature flags для планируемых функций
            $FEATURE_REPORTS_UI = false; // true для включения UI отчётов
            $FEATURE_NOTIFICATIONS_UI = false; // true для включения UI уведомлений
            ?>

            <?php if ($FEATURE_REPORTS_UI): ?>
                <a class="nku-nav__item <?php echo $isActive('report') ? 'is-active' : ''; ?>" href="<?php echo CHtml::encode(Yii::app()->createUrl('/report/index')); ?>">
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
                <a class="nku-nav__item <?php echo $isActive('notification') ? 'is-active' : ''; ?>" href="<?php echo CHtml::encode(Yii::app()->createUrl('/notification/index')); ?>">
                    <i class="fas fa-bell" aria-hidden="true"></i> <span>Уведомления</span>
                </a>
            <?php else: ?>
                <div class="nku-nav__item nku-nav__item--disabled" title="Функционал в разработке">
                    <i class="fas fa-bell" aria-hidden="true"></i> 
                    <span>Уведомления</span>
                    <span class="nku-badge nku-badge--xs nku-badge--warning ms-auto">Soon</span>
                </div>
            <?php endif; ?>

            <?php if ($role === 'admin'): ?>
                <div class="nku-nav__section">Администрирование</div>
                <a class="nku-nav__item <?php echo $isActive('admin') ? 'is-active' : ''; ?>" href="<?php echo CHtml::encode(Yii::app()->createUrl('/admin')); ?>">
                    <i class="fas fa-gear" aria-hidden="true"></i> <span>Админка</span>
                </a>
            <?php endif; ?>

            <div class="nku-nav__section">Аккаунт</div>
            <a class="nku-nav__item" href="<?php echo CHtml::encode(Yii::app()->createUrl('/site/logout')); ?>">
                <i class="fas fa-right-from-bracket" aria-hidden="true"></i> <span>Выход</span>
            </a>
        <?php endif; ?>
    </nav>
</aside>


