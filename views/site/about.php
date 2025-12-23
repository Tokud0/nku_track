<?php

/** @var yii\web\View $this */

use yii\helpers\Html;

$this->title = 'О нас';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="site-about">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="nku-card" style="margin-top: 2rem;">
                <div class="nku-card__header">
                    <h1 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        <?= Html::encode($this->title) ?>
                    </h1>
                </div>
                <div class="nku-card__body">
                    <div class="mb-4">
                        <p class="lead">
                            Приложение <strong>KU Track</strong> создано разработчиками <strong>центра искусственного интеллекта</strong> для эффективного управления проектами и задачами.
                        </p>
                        <p>
                            Система предназначена для организации работы команд, отслеживания прогресса проектов и координации задач между сотрудниками различных подразделений.
                        </p>
                    </div>
                    
                    <div class="nku-card" style="background: var(--nku-color-primary-light); border: none;">
                        <div class="nku-card__body">
                            <h5 class="mb-3">
                                <i class="fas fa-headset me-2"></i>
                                Обратная связь и техническая поддержка
                            </h5>
                            <p class="mb-2">
                                Для обратной связи или технической поддержки обращайтесь на почту:
                            </p>
                            <p class="mb-0">
                                <a href="mailto:danilchaykin@mail.ru" class="fw-semibold text-decoration-none">
                                    <i class="fas fa-envelope me-2"></i>
                                    danilchaykin@mail.ru
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
