<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\Department $department */
/** @var app\models\Roadmap|null $roadmap */

$this->title = 'Дорожная карта: ' . $department->name;
$this->params['breadcrumbs'][] = ['label' => 'Админка', 'url' => ['/admin']];
$this->params['breadcrumbs'][] = ['label' => 'Дорожные карты', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="roadmap-view">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= Html::encode($this->title) ?></h1>
        <div>
            <?php if ($roadmap): ?>
                <?= Html::a('Редактировать', ['update', 'id' => (string)$department->_id], ['class' => 'btn btn-primary']) ?>
                <?= Html::a('Удалить', ['delete', 'id' => (string)$department->_id], [
                    'class' => 'btn btn-danger',
                    'data' => [
                        'confirm' => 'Вы уверены, что хотите удалить дорожную карту этого подразделения?',
                        'method' => 'post',
                    ],
                ]) ?>
            <?php else: ?>
                <?= Html::a('Создать дорожную карту', ['create', 'id' => (string)$department->_id], ['class' => 'btn btn-success']) ?>
            <?php endif; ?>
            <?= Html::a('Назад к списку', ['index'], ['class' => 'btn btn-secondary']) ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Подразделение: <?= Html::encode($department->name) ?></h5>
        </div>
        <div class="card-body">
            <?php if (!$roadmap): ?>
                <div class="alert alert-info">
                    <p>Для этого подразделения еще не создана дорожная карта.</p>
                    <?= Html::a('Создать дорожную карту', ['create', 'id' => (string)$department->_id], ['class' => 'btn btn-success']) ?>
                </div>
            <?php else: ?>
                <?php
                $stages = $roadmap->stages;
                if (empty($stages)):
                ?>
                    <div class="alert alert-warning">
                        <p>Дорожная карта создана, но этапы еще не добавлены.</p>
                        <?= Html::a('Добавить этапы', ['update', 'id' => (string)$department->_id], ['class' => 'btn btn-primary']) ?>
                    </div>
                <?php else: ?>
                    <div class="roadmap-timeline">
                        <?php foreach ($stages as $index => $stage): ?>
                            <div class="card mb-4 stage-card">
                                <div class="card-header bg-primary text-white">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">Этап <?= $index + 1 ?>: <?= Html::encode($stage->name) ?></h5>
                                        <span class="badge bg-light text-dark">
                                            <?= $stage->start_month ?> - <?= $stage->end_month ?> месяцев
                                        </span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <?php if ($stage->description): ?>
                                        <p class="card-text"><strong>Описание:</strong> <?= nl2br(Html::encode($stage->description)) ?></p>
                                    <?php endif; ?>
                                    
                                    <?php
                                    $goals = $stage->goals;
                                    if (!empty($goals)):
                                    ?>
                                        <h6 class="mt-3">Цели этапа:</h6>
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
                                    <?php else: ?>
                                        <p class="text-muted">Цели этапа не добавлены</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-3">
                        <?= Html::a('Редактировать дорожную карту', ['update', 'id' => (string)$department->_id], ['class' => 'btn btn-primary']) ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

</div>

