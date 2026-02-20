<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\ChatbotFile $model */

$this->title = 'Просмотр: ' . $model->original_name;
$this->params['breadcrumbs'][] = ['label' => 'Файлы для чат-бота', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$this->registerCss(<<<CSS
[data-theme="dark"] .chatbot-file-view .card,
[data-theme="dark"] .chatbot-file-view .card .card-header,
[data-theme="dark"] .chatbot-file-view .card .card-body,
[data-theme="dark"] .chatbot-file-view .card .card-body pre {
    background-color: #23262e !important;
    color: #e8eaed !important;
    border-color: #3b4049;
}
[data-theme="dark"] .chatbot-file-view .btn-back-to-list {
    background-color: transparent;
    color: #e8eaed;
    border: 1px solid #3b4049;
}
[data-theme="dark"] .chatbot-file-view .btn-back-to-list:hover {
    background-color: #2c3038;
    color: #e8eaed;
    border-color: #5a8ae5;
}
CSS
);
?>
<div class="chatbot-file-view">
    <h1><?= Html::encode($this->title) ?></h1>
    <p>
        <?= Html::a('К списку', ['index'], ['class' => 'btn btn-outline-secondary btn-back-to-list']) ?>
    </p>
    <div class="card">
        <div class="card-header">Текст из файла (для бота)</div>
        <div class="card-body">
            <pre class="mb-0" style="white-space: pre-wrap; max-height: 70vh; overflow: auto;"><?= Html::encode($model->content_text ?? '') ?></pre>
        </div>
    </div>
</div>
