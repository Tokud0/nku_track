<?php
/** @var yii\web\View $this */
/** @var \app\models\User|null $user */

use yii\bootstrap5\Html;
use yii\helpers\Url;

$isGuest = Yii::$app->user->isGuest;
$user = $user ?? ($isGuest ? null : Yii::$app->user->identity);

$title = $this->title ?: Yii::$app->name;
?>

<header class="nku-topbar" role="banner">
    <!-- Left: mobile menu + title + search -->
    <div class="nku-topbar__left">
        <button
            type="button"
            class="nku-topbar__icon d-lg-none"
            aria-label="Открыть меню"
            onclick="document.body.classList.toggle('nku-sidebar-open');"
        >
            <i class="fas fa-bars" aria-hidden="true"></i>
        </button>

    </div>

    <!-- Right: notifications + user menu -->
    <div class="nku-topbar__right">
        <?php if ($isGuest): ?>
            <a class="nku-btn nku-btn--secondary" href="<?= Html::encode(Url::to(['/site/login'])) ?>">Вход</a>
            <a class="nku-btn nku-btn--primary" href="<?= Html::encode(Url::to(['/site/signup'])) ?>">Регистрация</a>
        <?php else: ?>
            <!-- User dropdown -->
            <div class="dropdown">
                <button
                    class="nku-topbar__user"
                    type="button"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                    title="<?= Html::encode($user->fio) ?>"
                >
                    <div class="nku-topbar__user-avatar">
                        <?php
                        // Инициалы пользователя
                        $nameParts = explode(' ', $user->fio);
                        $initials = '';
                        foreach (array_slice($nameParts, 0, 2) as $part) {
                            $initials .= mb_substr($part, 0, 1);
                        }
                        echo Html::encode(mb_strtoupper($initials));
                        ?>
                    </div>
                    <span class="nku-topbar__user-name d-none d-md-inline">
                        <?= Html::encode($user->fio) ?>
                    </span>
                    <i class="fas fa-chevron-down" style="font-size: 10px;"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li>
                        <a class="dropdown-item" href="<?= Html::encode(Url::to(['/profile/index'])) ?>">
                            <i class="fas fa-user-circle me-2"></i> Профиль
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <?= Html::beginForm(['/site/logout'], 'post') ?>
                            <button type="submit" class="dropdown-item text-danger">
                                <i class="fas fa-sign-out-alt me-2"></i> Выход
                            </button>
                        <?= Html::endForm() ?>
                    </li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</header>


