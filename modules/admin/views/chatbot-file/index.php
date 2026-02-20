<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\ChatbotFile[] $files */

$this->title = 'Файлы для чат-бота';
$this->params['breadcrumbs'][] = ['label' => 'Админка', 'url' => ['/admin']];
$this->params['breadcrumbs'][] = $this->title;

$this->registerCss(<<<CSS
[data-theme="dark"] .chatbot-file-index .card .card-body .card-title { color: #fff !important; }
[data-theme="dark"] .chatbot-file-index .card .card-body .card-text.text-muted { color: #b0b5bc !important; }
CSS
);
?>
<div class="chatbot-file-index">
    <h1><?= Html::encode($this->title) ?></h1>

    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Загрузить файл</h5>
            <p class="card-text text-muted">Текст из файлов используется как база знаний чат-бота. Разрешены: Word (docx), блокнот (txt), Excel (xlsx).</p>
            <?php $form = \yii\widgets\ActiveForm::begin([
                'action' => ['upload'],
                'method' => 'post',
                'options' => ['enctype' => 'multipart/form-data'],
            ]); ?>
            <div class="input-group">
                <input type="file" name="chatbot_file" class="form-control" accept=".txt,.docx,.xlsx" required>
                <button type="submit" class="btn btn-primary">Загрузить</button>
            </div>
            <?php \yii\widgets\ActiveForm::end(); ?>
        </div>
    </div>

    <h5>Загруженные файлы</h5>
    <?php if (empty($files)): ?>
        <p class="text-muted">Нет загруженных файлов.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Имя файла</th>
                        <th>Дата</th>
                        <th>Размер</th>
                        <th>Тип</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($files as $f):
                        $size = is_file($f->stored_path) ? filesize($f->stored_path) : 0;
                        $ext = pathinfo($f->original_name ?? '', PATHINFO_EXTENSION);
                        $active = $f->getIsActiveForContext();
                    ?>
                        <tr>
                            <td><?= Html::encode($f->original_name) ?></td>
                            <td><?= $f->created_at ? date('d.m.Y H:i', $f->created_at->toDateTime()->getTimestamp()) : '—' ?></td>
                            <td><?= $size ? Yii::$app->formatter->asShortSize($size) : '—' ?></td>
                            <td><?= $ext ? strtoupper($ext) : '—' ?></td>
                            <td class="text-nowrap">
                                <?= Html::a('Просмотр', ['view', 'id' => (string)$f->_id], ['class' => 'btn btn-sm btn-primary me-1']) ?>
                                <?= is_file($f->stored_path) ? Html::a('Скачать', ['download', 'id' => (string)$f->_id], ['class' => 'btn btn-sm btn-success me-1']) : '' ?>
                                <?= Html::a($active ? 'Деактивировать' : 'Активировать', ['toggle-active', 'id' => (string)$f->_id], [
                                    'class' => 'btn btn-sm me-1 ' . ($active ? 'btn-warning' : 'btn-success'),
                                    'data-method' => 'post',
                                ]) ?>
                                <?= Html::a('Удалить', ['delete', 'id' => (string)$f->_id], [
                                    'class' => 'btn btn-sm btn-danger',
                                    'data-method' => 'post',
                                    'data-confirm' => 'Удалить файл из базы бота?',
                                ]) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
