<?php

use yii\helpers\Html;
use yii\widgets\Pjax;

/** @var yii\web\View $this */
/** @var app\modules\admin\models\RoadmapSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var array $roadmaps */

$this->title = 'Дорожные карты подразделений';
$this->params['breadcrumbs'][] = ['label' => 'Админка', 'url' => ['/admin']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="roadmap-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php Pjax::begin(); ?>
    <?php echo $this->render('_search', ['model' => $searchModel]); ?>

    <?php if ($dataProvider->getTotalCount() == 0): ?>
        <div class="alert alert-info">
            Подразделения не найдены.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Подразделение</th>
                        <th>Статус дорожной карты</th>
                        <th>Количество этапов</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dataProvider->getModels() as $department): ?>
                        <?php
                        $roadmap = $roadmaps[(string)$department->_id] ?? null;
                        $stagesCount = $roadmap ? count($roadmap->stages) : 0;
                        ?>
                        <tr>
                            <td><?= Html::encode($department->name) ?></td>
                            <td>
                                <?php if ($roadmap): ?>
                                    <span class="badge bg-success">Создана</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Не создана</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $stagesCount ?></td>
                            <td>
                                <?php if ($roadmap): ?>
                                    <?= Html::a('Просмотр', ['view', 'id' => (string)$department->_id], ['class' => 'btn btn-sm btn-info']) ?>
                                    <?= Html::a('Редактировать', ['update', 'id' => (string)$department->_id], ['class' => 'btn btn-sm btn-primary']) ?>
                                    <?= Html::a('Удалить', ['delete', 'id' => (string)$department->_id], [
                                        'class' => 'btn btn-sm btn-danger',
                                        'data' => [
                                            'confirm' => 'Вы уверены, что хотите удалить дорожную карту этого подразделения?',
                                            'method' => 'post',
                                        ],
                                    ]) ?>
                                <?php else: ?>
                                    <?= Html::a('Создать дорожную карту', ['create', 'id' => (string)$department->_id], ['class' => 'btn btn-sm btn-success']) ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?= $this->render('@app/modules/admin/views/_admin-pager', [
            'pagination' => $dataProvider->pagination,
            'totalCount' => $dataProvider->totalCount,
        ]) ?>
    <?php endif; ?>

    <?php Pjax::end(); ?>

</div>

