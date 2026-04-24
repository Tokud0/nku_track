<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
use app\models\User;

/** @var yii\web\View $this */
/** @var app\modules\admin\models\UserSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Управление пользователями';
$this->params['breadcrumbs'][] = ['label' => 'Админка', 'url' => ['/admin']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="user-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Создать пользователя', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?php Pjax::begin(); ?>
    <?php echo $this->render('_search', ['model' => $searchModel]); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => null,
        'tableOptions' => ['class' => 'table table-hover table-striped'],
        'options' => ['class' => 'table-responsive'],
        'layout' => "{summary}\n{items}",
        'columns' => [
            [
                'class' => 'yii\grid\SerialColumn',
                'header' => '#',
                'contentOptions' => ['style' => 'width: 50px; text-align: center;'],
            ],
            [
                'attribute' => 'fio',
                'label' => 'ФИО',
                'format' => 'raw',
                'value' => function($model) {
                    return Html::tag('strong', Html::encode($model->fio));
                },
                'contentOptions' => ['style' => 'font-weight: 500;'],
            ],
            [
                'attribute' => 'email',
                'label' => 'EMAIL',
                'format' => 'email',
                'value' => function($model) {
                    return Html::encode($model->email);
                },
            ],
            [
                'attribute' => 'role',
                'label' => 'РОЛЬ',
                'format' => 'raw',
                'value' => function($model) {
                    $roles = [
                        User::ROLE_ADMIN => ['name' => 'Администратор', 'class' => 'danger'],
                        User::ROLE_HEAD => ['name' => 'Руководитель', 'class' => 'warning'],
                        User::ROLE_RECTOR => ['name' => 'Ректор', 'class' => 'info'],
                        User::ROLE_TOP_MANAGER => ['name' => 'Топ-менеджер', 'class' => 'success'],
                        User::ROLE_MANAGER => ['name' => 'Менеджер', 'class' => 'secondary'],
                        User::ROLE_EXECUTOR => ['name' => 'Исполнитель', 'class' => 'primary'],
                    ];
                    $roleInfo = $roles[$model->role] ?? ['name' => $model->role, 'class' => 'default'];
                    return Html::tag('span', $roleInfo['name'], [
                        'class' => 'label label-' . $roleInfo['class']
                    ]);
                },
            ],
            [
                'attribute' => 'department_id',
                'label' => 'ПОДРАЗДЕЛЕНИЕ',
                'format' => 'raw',
                'value' => function($model) {
                    if ($model->department) {
                        return Html::tag('span', Html::encode($model->department->name), [
                            'class' => 'badge bg-secondary'
                        ]);
                    }
                    return Html::tag('span', '-', ['style' => 'color: #999;']);
                },
            ],
            [
                'attribute' => 'created_at',
                'label' => 'ДАТА СОЗДАНИЯ',
                'format' => 'raw',
                'value' => function($model) {
                    if ($model->created_at instanceof \MongoDB\BSON\UTCDateTime) {
                        $date = date('d.m.Y', $model->created_at->toDateTime()->getTimestamp());
                        $time = date('H:i', $model->created_at->toDateTime()->getTimestamp());
                        return Html::tag('div', $date, ['style' => 'font-weight: 500;']) . 
                               Html::tag('small', $time, ['style' => 'color: #666;']);
                    }
                    return '-';
                },
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'header' => 'ДЕЙСТВИЯ',
                'headerOptions' => ['style' => 'text-align: center; width: 120px;'],
                'contentOptions' => ['style' => 'text-align: center;'],
                'template' => '{view} {update} {delete}',
                'buttons' => [
                    'view' => function ($url, $model) {
                        return Html::a('<span class="glyphicon glyphicon-eye-open"></span>', $url, [
                            'title' => 'Просмотр',
                            'class' => 'btn btn-xs btn-info',
                            'style' => 'margin-right: 3px;',
                        ]);
                    },
                    'update' => function ($url, $model) {
                        return Html::a('<span class="glyphicon glyphicon-pencil"></span>', $url, [
                            'title' => 'Редактировать',
                            'class' => 'btn btn-xs btn-primary',
                            'style' => 'margin-right: 3px;',
                        ]);
                    },
                    'delete' => function ($url, $model) {
                        return Html::a('<span class="glyphicon glyphicon-trash"></span>', $url, [
                            'title' => 'Удалить',
                            'class' => 'btn btn-xs btn-danger',
                            'data-confirm' => 'Вы уверены, что хотите удалить этого пользователя?',
                            'data-method' => 'post',
                        ]);
                    },
                ],
            ],
        ],
    ]); ?>

    <?= $this->render('@app/modules/admin/views/_admin-pager', [
        'pagination' => $dataProvider->pagination,
        'totalCount' => $dataProvider->totalCount,
    ]) ?>

    <?php Pjax::end(); ?>

</div>

<style>
.user-index .table {
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.user-index .table thead {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
}

.user-index .table thead th {
    border: none;
    padding: 15px;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 12px;
    letter-spacing: 0.5px;
}

.user-index .table tbody tr {
    transition: all 0.2s ease;
}

.user-index .table tbody tr:hover {
    background-color: #f8f9fa;
    transform: scale(1.01);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.user-index .table tbody td {
    padding: 15px;
    vertical-align: middle;
    border-top: 1px solid #e9ecef;
}

.user-index .label {
    padding: 6px 12px;
    font-size: 11px;
    font-weight: 600;
    border-radius: 20px;
    display: inline-block;
}

.user-index .badge {
    padding: 5px 10px;
    font-size: 11px;
    border-radius: 12px;
}

.user-index .btn-xs {
    padding: 4px 8px;
    font-size: 12px;
    border-radius: 4px;
    border: none;
    transition: all 0.2s ease;
}

.user-index .btn-xs:hover {
    transform: translateY(-2px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.user-index .table-responsive {
    border-radius: 8px;
    overflow: hidden;
}
</style>

