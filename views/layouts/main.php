<?php

/** @var yii\web\View $this */
/** @var string $content */

use app\assets\AppAsset;
use app\widgets\Alert;
use yii\bootstrap5\Html;

AppAsset::register($this);
$this->registerCssFile('@web/css/nku-track.css', ['depends' => [\app\assets\AppAsset::class]]);

$this->registerCsrfMetaTags();
$this->registerMetaTag(['charset' => Yii::$app->charset], 'charset');
$this->registerMetaTag(['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1, shrink-to-fit=no']);
$this->registerMetaTag(['name' => 'description', 'content' => $this->params['meta_description'] ?? '']);
$this->registerMetaTag(['name' => 'keywords', 'content' => $this->params['meta_keywords'] ?? '']);
$this->registerLinkTag(['rel' => 'icon', 'type' => 'image/x-icon', 'href' => Yii::getAlias('@web/icon.png')]);
$this->registerLinkTag(['rel' => 'shortcut icon', 'type' => 'image/x-icon', 'href' => Yii::getAlias('@web/icon.png')]);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" class="h-100">
<head>
    <script>
(function(){var t=localStorage.getItem('nku-theme');if(t==='dark')document.documentElement.setAttribute('data-theme','dark');})();
    </script>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body class="h-100">
<?php $this->beginBody() ?>

<?php
$user = Yii::$app->user->isGuest ? null : Yii::$app->user->identity;
?>

<div class="nku-shell">
    <?= $this->render('//partials/_sidebar', ['user' => $user]) ?>

    <div class="nku-main">
        <?= $this->render('//partials/_topbar', ['user' => $user]) ?>

        <main class="nku-content" role="main">
            <?= $this->render('//partials/_breadcrumbs') ?>
            <?= Alert::widget() ?>
            <?= $content ?>
        </main>

        <footer id="footer" class="nku-footer py-3 border-top">
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
    </div>
</div>

<?php $this->registerJsFile('@web/js/theme-toggle.js', ['position' => \yii\web\View::POS_END]) ?>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
