<?php
/**
 * Yii1-compatible layout scaffold for NKU Track.
 * Note: This repo is currently Yii2, but these files are provided as requested
 * for a Yii1-style structure (protected/views/...). They do not affect runtime
 * unless the app is actually Yii1.
 */
?><!doctype html>
<html lang="<?php echo CHtml::encode(Yii::app()->language); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo CHtml::encode($this->pageTitle); ?></title>
    <link rel="icon" type="image/x-icon" href="<?php echo CHtml::encode(Yii::app()->baseUrl); ?>/favicon.ico">
    <link rel="stylesheet" href="<?php echo CHtml::encode(Yii::app()->baseUrl); ?>/css/nku-track.css">
</head>
<body class="h-100">
<div class="nku-shell">
    <?php $this->renderPartial('//partials/_sidebar'); ?>
    <div class="nku-main">
        <?php $this->renderPartial('//partials/_topbar'); ?>
        <main class="nku-content" role="main">
            <?php $this->renderPartial('//partials/_breadcrumbs'); ?>
            <?php echo $content; ?>
        </main>
        <footer id="footer" class="py-3 bg-light border-top">
            <div class="container-fluid">
                <div class="row text-muted">
                    <div class="col-md-6 text-center text-md-start">
                        <small>&copy; <?php echo CHtml::encode(Yii::app()->name); ?> <?php echo date('Y'); ?></small>
                    </div>
                    <div class="col-md-6 text-center text-md-end">
                        <small>Powered by Yii</small>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</div>
</body>
</html>


