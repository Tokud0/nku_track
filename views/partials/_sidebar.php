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

            <a class="nku-nav__item <?= ($isActive('global-project') || $isActive('direction')) ? 'is-active' : '' ?>" href="<?= Html::encode(Url::to(['/direction/index'])) ?>">
                <i class="fas fa-globe" aria-hidden="true"></i> <span>Глобальный проект</span>
            </a>

            <?php if ($user && $user->department_id): ?>
                <a class="nku-nav__item <?= $isActive('department-staff') ? 'is-active' : '' ?>" href="<?= Html::encode(Url::to(['/department-staff/index'])) ?>">
                    <i class="fas fa-users" aria-hidden="true"></i> <span>Состав подразделения</span>
                </a>
            <?php endif; ?>
            <?php if ($user && $user->department_id && in_array($user->role, [\app\models\User::ROLE_HEAD, \app\models\User::ROLE_TOP_MANAGER], true)): ?>
                <a class="nku-nav__item <?= $isActive('task-executor-request') ? 'is-active' : '' ?>" href="<?= Html::encode(Url::to(['/task-executor-request/index'])) ?>">
                    <i class="fas fa-user-check" aria-hidden="true"></i> <span>Заявки на прикрепление</span>
                </a>
            <?php endif; ?>

            <?php if ($user && in_array($user->role, [\app\models\User::ROLE_HEAD, \app\models\User::ROLE_TOP_MANAGER], true)): ?>
                <a class="nku-nav__item <?= $isActive('roadmap') ? 'is-active' : '' ?>" href="<?= Html::encode(Url::to(['/roadmap/index'])) ?>">
                    <i class="fas fa-road" aria-hidden="true"></i> <span>Дорожная карта</span>
                </a>
            <?php endif; ?>

            <?php if ($user && $user->role === \app\models\User::ROLE_ADMIN): ?>
                <div class="nku-nav__section">Администрирование</div>
                <a class="nku-nav__item <?= $isActive('admin') ? 'is-active' : '' ?>" href="<?= Html::encode(Url::to(['/admin'])) ?>">
                    <i class="fas fa-gear" aria-hidden="true"></i> <span>Админка</span>
                </a>
            <?php endif; ?>

            <div class="nku-nav__section">Аккаунт</div>
            <a class="nku-nav__item <?= $isActive('profile') ? 'is-active' : '' ?>" href="<?= Html::encode(Url::to(['/profile/index'])) ?>">
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


