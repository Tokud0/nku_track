<?php

use yii\helpers\Html;
use app\models\Roadmap;

/** @var yii\web\View $this */
/** @var app\models\Roadmap|null $roadmap */

$this->title = 'Глобальная дорожная карта';
$this->params['breadcrumbs'][] = ['label' => 'Глобальный проект', 'url' => ['/global-project/index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="roadmap-view">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= Html::encode($this->title) ?></h1>
        <div>
            <?php if ($roadmap): ?>
                <?= Html::a('Редактировать', ['update'], ['class' => 'btn btn-primary']) ?>
            <?php else: ?>
                <?= Html::a('Создать дорожную карту', ['index'], ['class' => 'btn btn-success']) ?>
            <?php endif; ?>
            <?= Html::a('Назад к проекту', ['/global-project/index'], ['class' => 'btn btn-secondary']) ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Глобальная дорожная карта университета</h5>
        </div>
        <div class="card-body">
            <?php if (!$roadmap): ?>
                <div class="alert alert-info">
                    <p>Глобальная дорожная карта еще не создана.</p>
                    <?= Html::a('Создать дорожную карту', ['index'], ['class' => 'btn btn-success']) ?>
                </div>
            <?php else: ?>
                <?php
                $stages = $roadmap->stages;
                if (empty($stages)):
                ?>
                    <div class="alert alert-warning">
                        <p>Дорожная карта создана, но этапы еще не добавлены.</p>
                        <?= Html::a('Добавить этапы', ['update'], ['class' => 'btn btn-primary']) ?>
                    </div>
                <?php else: ?>
                    <div class="roadmap-timeline">
                        <?php foreach ($stages as $index => $stage): ?>
                            <div class="card mb-4 stage-card">
                                <div class="card-header bg-primary text-white">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">
                                            Этап <?= $index + 1 ?>: <?= Html::encode($stage->name) ?>
                                            <?php if (!empty($stage->is_completed)): ?>
                                                <span class="badge bg-success ms-2">Завершен</span>
                                            <?php endif; ?>
                                        </h5>
                                        <div class="text-end">
                                            <span class="badge bg-light text-dark">
                                                <?= $stage->start_month ?> - <?= $stage->end_month ?> месяцев
                                            </span>
                                            <?php if ($stage->start_date instanceof \MongoDB\BSON\UTCDateTime || $stage->end_date instanceof \MongoDB\BSON\UTCDateTime): ?>
                                                <div class="mt-1">
                                                    <small class="text-white">
                                                        <?php if ($stage->start_date instanceof \MongoDB\BSON\UTCDateTime): ?>
                                                            с <?= date('d.m.Y', $stage->start_date->toDateTime()->getTimestamp()) ?>
                                                        <?php endif; ?>
                                                        <?php if ($stage->end_date instanceof \MongoDB\BSON\UTCDateTime): ?>
                                                            по <?= date('d.m.Y', $stage->end_date->toDateTime()->getTimestamp()) ?>
                                                        <?php endif; ?>
                                                    </small>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($stage->is_completed) && !empty($stage->completion_format)): ?>
                                        <div class="alert alert-success mb-3">
                                            <strong><i class="fas fa-check-circle me-2"></i>Этап завершен</strong>
                                            <p class="mb-0 mt-2"><strong>Формат завершения:</strong> <?= nl2br(Html::encode($stage->completion_format)) ?></p>
                                            <?php if ($stage->completed_at instanceof \MongoDB\BSON\UTCDateTime): ?>
                                                <small class="text-muted">
                                                    <i class="far fa-calendar me-1"></i>
                                                    Дата завершения: <?= date('d.m.Y', $stage->completed_at->toDateTime()->getTimestamp()) ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($stage->description): ?>
                                        <p class="card-text"><strong>Описание:</strong> <?= nl2br(Html::encode($stage->description)) ?></p>
                                    <?php endif; ?>
                                    
                                    <?php
                                    $goals = $stage->goals;
                                    if (!empty($goals)):
                                    ?>
                                        <div class="mt-3">
                                            <h6><i class="fas fa-bullseye me-2"></i>Цели этапа:</h6>
                                            <ul class="list-group">
                                                <?php foreach ($goals as $goal): ?>
                                                    <li class="list-group-item">
                                                        <strong><?= Html::encode($goal->title) ?></strong>
                                                        <?php if ($goal->description): ?>
                                                            <br><small class="text-muted"><?= nl2br(Html::encode($goal->description)) ?></small>
                                                        <?php endif; ?>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

