<?php

use yii\data\Pagination;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * Единый пейджер админки.
 *
 * @var yii\web\View $this
 * @var Pagination $pagination
 * @var int $totalCount
 * @var int[] $pageSizeOptions
 */

if (!isset($pagination) || !$pagination instanceof Pagination) {
    return;
}

$pageSizeOptions = $pageSizeOptions ?? [10, 20, 50, 100];
$totalCount = isset($totalCount) ? (int)$totalCount : (int)$pagination->totalCount;
$pageSize = (int)$pagination->pageSize;
$pageCount = (int)$pagination->pageCount;
$currentPage = (int)$pagination->page; // 0-based
$pageParam = $pagination->pageParam;
$pageSizeParam = $pagination->pageSizeParam;

if ($totalCount <= 0) {
    return;
}

$from = $currentPage * $pageSize + 1;
$to = min($totalCount, $from + $pageSize - 1);

// Формируем URL для смены страницы / per-page
$currentParams = Yii::$app->request->getQueryParams();
$route = '/' . Yii::$app->controller->getRoute();
$buildUrl = function (array $override) use ($currentParams, $route) {
    $params = array_merge($currentParams, $override);
    foreach ($params as $k => $v) {
        if ($v === null || $v === '') {
            unset($params[$k]);
        }
    }
    return Url::to(array_merge([$route], $params));
};

// Список номеров страниц с сокращением: 1 … 4 5 [6] 7 8 … 20
$windowSize = 2; // сколько соседей показать вокруг текущей
$pages = [];
if ($pageCount <= 7 + $windowSize * 2) {
    for ($i = 0; $i < $pageCount; $i++) {
        $pages[] = $i;
    }
} else {
    $pages[] = 0;
    $start = max(1, $currentPage - $windowSize);
    $end = min($pageCount - 2, $currentPage + $windowSize);
    if ($start > 1) {
        $pages[] = '...';
    }
    for ($i = $start; $i <= $end; $i++) {
        $pages[] = $i;
    }
    if ($end < $pageCount - 2) {
        $pages[] = '...';
    }
    $pages[] = $pageCount - 1;
}
?>

<div class="admin-pager d-flex flex-wrap align-items-center justify-content-between gap-3 my-3">
    <div class="admin-pager__summary text-muted">
        Показано <strong><?= $from ?></strong>–<strong><?= $to ?></strong> из <strong><?= $totalCount ?></strong>
        · Страница <strong><?= $currentPage + 1 ?></strong> из <strong><?= max(1, $pageCount) ?></strong>
    </div>

    <?php if ($pageCount > 1): ?>
        <nav aria-label="Пагинация">
            <ul class="pagination pagination-lg mb-0 admin-pager__list">
                <li class="page-item <?= $currentPage === 0 ? 'disabled' : '' ?>">
                    <?= Html::a('« Первая', $buildUrl([$pageParam => null]),
                        ['class' => 'page-link', 'title' => 'К первой странице']) ?>
                </li>
                <li class="page-item <?= $currentPage === 0 ? 'disabled' : '' ?>">
                    <?= Html::a('‹ Назад', $buildUrl([$pageParam => $currentPage > 0 ? $currentPage : null]),
                        ['class' => 'page-link', 'title' => 'Предыдущая страница']) ?>
                </li>

                <?php foreach ($pages as $p): ?>
                    <?php if ($p === '...'): ?>
                        <li class="page-item disabled"><span class="page-link">…</span></li>
                    <?php else: ?>
                        <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>">
                            <?= Html::a((string)($p + 1), $buildUrl([$pageParam => $p === 0 ? null : ($p + 1)]),
                                ['class' => 'page-link']) ?>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>

                <li class="page-item <?= $currentPage >= $pageCount - 1 ? 'disabled' : '' ?>">
                    <?= Html::a('Вперёд ›', $buildUrl([$pageParam => $currentPage + 2]),
                        ['class' => 'page-link', 'title' => 'Следующая страница']) ?>
                </li>
                <li class="page-item <?= $currentPage >= $pageCount - 1 ? 'disabled' : '' ?>">
                    <?= Html::a('Последняя »', $buildUrl([$pageParam => $pageCount]),
                        ['class' => 'page-link', 'title' => 'К последней странице']) ?>
                </li>
            </ul>
        </nav>
    <?php endif; ?>

    <div class="admin-pager__per-page d-flex align-items-center gap-2">
        <label class="text-muted mb-0">Показывать по:</label>
        <select class="form-select form-select-sm admin-pager__per-page-select" style="width: auto;">
            <?php foreach ($pageSizeOptions as $opt): ?>
                <option value="<?= (int)$opt ?>" <?= (int)$opt === $pageSize ? 'selected' : '' ?>><?= (int)$opt ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<style>
.admin-pager .page-link { padding: .5rem .9rem; }
.admin-pager .page-item.active .page-link { background-color: #0d6efd; border-color: #0d6efd; }
.admin-pager .page-item.disabled .page-link { color: #aaa; }
.admin-pager__summary { font-size: 0.95rem; }
</style>

<?php
$perPageUrls = [];
foreach ($pageSizeOptions as $opt) {
    $perPageUrls[(int)$opt] = $buildUrl([$pageParam => null, $pageSizeParam => (int)$opt]);
}
$perPageUrlsJson = json_encode($perPageUrls);
$this->registerJs(<<<JS
(function() {
    var urls = $perPageUrlsJson;
    document.querySelectorAll('.admin-pager__per-page-select').forEach(function(sel) {
        sel.addEventListener('change', function() {
            var v = parseInt(this.value, 10);
            if (urls[v]) { window.location.href = urls[v]; }
        });
    });
})();
JS
);
