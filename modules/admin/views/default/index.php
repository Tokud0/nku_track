<?php

use yii\helpers\Html;
use app\models\User;

/** @var yii\web\View $this */
/** @var int $totalUsers */
/** @var int $totalAdmins */
/** @var int $totalHeads */
/** @var int $totalRectors */
/** @var int $totalTopManagers */
/** @var int $totalManagers */
/** @var int $totalExecutors */

$this->title = 'Панель администратора';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="admin-default-index">
    <h1><?= Html::encode($this->title) ?></h1>

    <div class="row mt-4">
        <!-- Статистика -->
        <div class="col-md-12 mb-4">
            <h3>Статистика</h3>
            <div class="row">
                <div class="col-md-3">
                    <div class="card text-white bg-warning mb-3">
                        <div class="card-header">Руководители</div>
                        <div class="card-body">
                            <h2 class="card-title"><?= $totalHeads ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-success mb-3">
                        <div class="card-header">Топ-менеджеры</div>
                        <div class="card-body">
                            <h2 class="card-title"><?= $totalTopManagers ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-secondary mb-3">
                        <div class="card-header">Менеджеры</div>
                        <div class="card-body">
                            <h2 class="card-title"><?= $totalManagers ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-info mb-3">
                        <div class="card-header">Исполнители</div>
                        <div class="card-body">
                            <h2 class="card-title"><?= $totalExecutors ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Действия администратора -->
        <div class="col-md-12">
            <h3>Действия</h3>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="glyphicon glyphicon-user"></i> Управление пользователями
                            </h5>
                            <p class="card-text">Создание, редактирование и удаление пользователей системы.</p>
                            <?= Html::a('Перейти к управлению', ['user/index'], ['class' => 'btn btn-primary']) ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="glyphicon glyphicon-briefcase"></i> Управление подразделениями
                            </h5>
                            <p class="card-text">Создание и управление подразделениями организации.</p>
                            <?= Html::a('Перейти к управлению', ['department/index'], ['class' => 'btn btn-primary']) ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="glyphicon glyphicon-road"></i> Дорожные карты подразделений
                            </h5>
                            <p class="card-text">Управление дорожными картами и этапами развития подразделений.</p>
                            <?= Html::a('Перейти к управлению', ['roadmap/index'], ['class' => 'btn btn-primary']) ?>
                        </div>
                    </div>
                </div>
                
                <!-- Здесь можно добавить другие действия в будущем -->
                <!--
                <div class="col-md-4 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="glyphicon glyphicon-folder-open"></i> Управление проектами
                            </h5>
                            <p class="card-text">Просмотр и управление всеми проектами системы.</p>
                            <?= Html::a('Перейти к управлению', ['project/index'], ['class' => 'btn btn-primary']) ?>
                        </div>
                    </div>
                </div>
                -->
            </div>
        </div>
    </div>
</div>

<style>
.admin-default-index .card {
    transition: transform 0.2s;
    height: 100%;
}

.admin-default-index .card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.admin-default-index .card-header {
    font-weight: bold;
}
</style>

