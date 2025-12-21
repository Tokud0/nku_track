<?php

/** @var yii\web\View $this */
/** @var string $content */

use app\assets\AppAsset;
use app\widgets\Alert;
use yii\bootstrap5\Breadcrumbs;
use yii\bootstrap5\Html;
use yii\bootstrap5\Nav;
use yii\bootstrap5\NavBar;

AppAsset::register($this);

$this->registerCsrfMetaTags();
$this->registerMetaTag(['charset' => Yii::$app->charset], 'charset');
$this->registerMetaTag(['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1, shrink-to-fit=no']);
$this->registerMetaTag(['name' => 'description', 'content' => $this->params['meta_description'] ?? '']);
$this->registerMetaTag(['name' => 'keywords', 'content' => $this->params['meta_keywords'] ?? '']);
$this->registerLinkTag(['rel' => 'icon', 'type' => 'image/x-icon', 'href' => Yii::getAlias('@web/favicon.ico')]);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" class="h-100">
<head>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body class="d-flex flex-column h-100">
<?php $this->beginBody() ?>

<header id="header">
    <?php
    NavBar::begin([
        'brandLabel' => '<i class="fas fa-tasks"></i> ' . Yii::$app->name,
        'brandUrl' => Yii::$app->homeUrl,
        'brandOptions' => ['encode' => false],
        'options' => ['class' => 'navbar-expand-md navbar-dark fixed-top', 'style' => 'background-color: #6B8E9F;']
    ]);
    $menuItems = [];
    
    if (!Yii::$app->user->isGuest) {
        $user = Yii::$app->user->identity;
        $menuItems[] = ['label' => '<i class="fas fa-home"></i> Главная', 'url' => ['/site/index'], 'encode' => false];
        $menuItems[] = ['label' => '<i class="fas fa-project-diagram"></i> Проекты', 'url' => ['/project/index'], 'encode' => false];
        
        // Дорожная карта доступна руководителю и топ-менеджеру
        if (in_array($user->role, [\app\models\User::ROLE_RECTOR, \app\models\User::ROLE_TOP_MANAGER])) {
            $menuItems[] = ['label' => '<i class="fas fa-road"></i> Дорожная карта', 'url' => ['/roadmap/index'], 'encode' => false];
        }
        
        // Админка доступна только админу
        if ($user->role === \app\models\User::ROLE_ADMIN) {
            $menuItems[] = ['label' => '<i class="fas fa-cog"></i> Админка', 'url' => ['/admin'], 'encode' => false];
        }
    } else {
        $menuItems[] = ['label' => 'Главная', 'url' => ['/site/index']];
        $menuItems[] = ['label' => 'О нас', 'url' => ['/site/about']];
        $menuItems[] = ['label' => 'Контакты', 'url' => ['/site/contact']];
    }
    
    if (Yii::$app->user->isGuest) {
        $menuItems[] = ['label' => 'Вход', 'url' => ['/site/login']];
        $menuItems[] = ['label' => 'Регистрация', 'url' => ['/site/signup']];
    } else {
        $user = Yii::$app->user->identity;
        $menuItems[] = '<li class="nav-item dropdown">'
            . Html::a(
                '<i class="fas fa-user"></i> ' . Html::encode($user->fio) . ' <i class="fas fa-caret-down"></i>',
                '#',
                [
                    'class' => 'nav-link dropdown-toggle',
                    'data-bs-toggle' => 'dropdown',
                    'role' => 'button',
                    'aria-haspopup' => 'true',
                    'aria-expanded' => 'false',
                    'encode' => false
                ]
            )
            . '<div class="dropdown-menu dropdown-menu-end">'
            . Html::a('<i class="fas fa-user-circle"></i> Профиль', ['/admin/user/view', 'id' => (string)$user->_id], [
                'class' => 'dropdown-item',
                'encode' => false
            ])
            . '<div class="dropdown-divider"></div>'
            . Html::beginForm(['/site/logout'], 'post', ['class' => 'd-inline'])
            . Html::submitButton(
                '<i class="fas fa-sign-out-alt"></i> Выход',
                ['class' => 'dropdown-item btn btn-link p-0 text-start w-100', 'style' => 'border: none; background: none;']
            )
            . Html::endForm()
            . '</div>'
            . '</li>';
    }
    
    echo Nav::widget([
        'options' => ['class' => 'navbar-nav'],
        'items' => $menuItems,
    ]);
    NavBar::end();
    ?>
</header>

<main id="main" class="flex-shrink-0" role="main" style="margin-top: 56px; padding-top: 20px;">
    <div class="container-fluid">
        <?php if (!empty($this->params['breadcrumbs'])): ?>
            <?= Breadcrumbs::widget(['links' => $this->params['breadcrumbs']]) ?>
        <?php endif ?>
        <?= Alert::widget() ?>
        <?= $content ?>
    </div>
</main>

<footer id="footer" class="mt-auto py-3 bg-light border-top">
    <div class="container-fluid">
        <div class="row text-muted">
            <div class="col-md-6 text-center text-md-start">
                <small>&copy; <?= Yii::$app->name ?> <?= date('Y') ?></small>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <small><?= Yii::powered() ?></small>
            </div>
        </div>
    </div>
</footer>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
