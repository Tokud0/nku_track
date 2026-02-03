<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\models\Project;

/** @var yii\web\View $this */
/** @var app\models\Project $model */
/** @var array $stagesData этапы из ТЗ с задачами: [ ['id','index','name','tasks'=>[]], ... ] */

$this->title = 'Mind Map: ' . $model->title;
$this->params['breadcrumbs'][] = ['label' => 'Проекты', 'url' => ['project/index']];
$this->params['breadcrumbs'][] = ['label' => $model->title, 'url' => ['project/view', 'id' => (string)$model->_id]];
$this->params['breadcrumbs'][] = 'Mind Map';

// Подключаем библиотеку vis-network для визуализации
$this->registerCssFile('https://unpkg.com/vis-network@latest/styles/vis-network.min.css');
$this->registerJsFile('https://unpkg.com/vis-network@latest/standalone/umd/vis-network.min.js', ['position' => \yii\web\View::POS_HEAD]);
// Подключаем библиотеки для экспорта в PDF
$this->registerJsFile('https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js', ['position' => \yii\web\View::POS_HEAD]);
$this->registerJsFile('https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js', ['position' => \yii\web\View::POS_HEAD]);
?>

<div class="mind-map-view">
    <div class="nku-card mb-4">
        <div class="nku-card__body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h1 class="mb-0"><?= Html::encode($this->title) ?></h1>
                    <p class="text-muted mb-0">
                        <i class="fas fa-project-diagram me-2"></i>
                        Этапы (из ТЗ) и задачи проекта. При открытии этапа видны его задачи.
                    </p>
                </div>
                <div>
                    <?= Html::a(
                        '<i class="fas fa-arrow-left me-2"></i>Назад к проекту',
                        ['view', 'id' => (string)$model->_id],
                        ['class' => 'nku-btn nku-btn--secondary']
                    ) ?>
                </div>
            </div>
        </div>
    </div>

    <div class="nku-card">
        <div class="nku-card__body p-0 position-relative">
            <div id="mindmap-container" style="width: 100%; height: 800px; border: 1px solid #e0e0e0;"></div>
            <!-- Кнопки экспорта -->
            <div class="mindmap-controls" style="position: absolute; top: 10px; right: 10px; z-index: 1000; display: flex; gap: 10px;">
                <button id="export-png-btn" class="btn btn-success" style="padding: 8px 16px; border-radius: 4px; display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <i class="fas fa-image"></i>
                    <span>Экспорт в PNG</span>
                </button>
                <button id="export-pdf-btn" class="btn btn-primary" style="padding: 8px 16px; border-radius: 4px; display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <i class="fas fa-file-pdf"></i>
                    <span>Экспорт в PDF</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Данные: этапы из ТЗ и задачи (подзадачи не показываем)
    const projectData = {
        id: 'project',
        title: <?= json_encode($model->title, JSON_UNESCAPED_UNICODE) ?>,
        description: <?= json_encode($model->description ?? '', JSON_UNESCAPED_UNICODE) ?>
    };
    const stagesData = <?= json_encode($stagesData ?? [], JSON_UNESCAPED_UNICODE) ?>;
    const taskViewBaseUrl = <?= json_encode(\yii\helpers\Url::to(['/task/view']), JSON_UNESCAPED_SLASHES) ?>;
    function taskViewUrl(taskId) {
        return taskViewBaseUrl + (taskViewBaseUrl.indexOf('?') >= 0 ? '&' : '?') + 'id=' + encodeURIComponent(taskId);
    }
    
    const nodes = [];
    const edges = [];
    
    // Центральный узел - проект
    nodes.push({
        id: 'project',
        label: projectData.title,
        title: projectData.description || projectData.title,
        shape: 'box',
        color: {
            background: '#4A90E2',
            border: '#2E5C8A',
            highlight: { background: '#5BA0F2', border: '#2E5C8A' }
        },
        font: { size: 20, color: '#FFFFFF', face: 'Arial', bold: true },
        widthConstraint: { maximum: 300 },
        level: 0
    });
    
    // Узлы: этапы (уровень 1) и задачи этапа (уровень 2, без подзадач)
    stagesData.forEach((stage, stageIndex) => {
        const stageId = stage.id;
        nodes.push({
            id: stageId,
            label: stage.name,
            title: 'Этап. Задач: ' + (stage.tasks ? stage.tasks.length : 0),
            shape: 'box',
            color: {
                background: '#6A5ACD',
                border: '#483D8B',
                highlight: { background: '#7B68EE', border: '#483D8B' }
            },
            font: { size: 16, color: '#FFFFFF', face: 'Arial', bold: true },
            widthConstraint: { maximum: 250 },
            level: 1
        });
        edges.push({
            from: 'project',
            to: stageId,
            color: { color: '#6A5ACD', highlight: '#7B68EE' },
            width: 3
        });
        
        (stage.tasks || []).forEach((task, taskIndex) => {
            const taskId = 'task_' + task.id;
            nodes.push({
                id: taskId,
                label: task.title,
                title: task.description || task.title,
                shape: 'box',
                url: taskViewUrl(task.id),
                color: {
                    background: '#50C878',
                    border: '#2E8B57',
                    highlight: { background: '#60D888', border: '#2E8B57' }
                },
                font: { size: 14, color: '#FFFFFF', face: 'Arial' },
                widthConstraint: { maximum: 220 },
                level: 2
            });
            edges.push({
                from: stageId,
                to: taskId,
                color: { color: '#50C878', highlight: '#60D888' },
                width: 2
            });
        });
    });
    
    // Если нет этапов/данных
    if (nodes.length === 1 && edges.length === 0) {
        nodes.push({
            id: 'empty',
            label: 'Нет этапов в ТЗ\n\nДобавьте этапы в ТЗ проекта и привяжите к ним задачи',
            shape: 'box',
            color: { background: '#E0E0E0', border: '#BDBDBD' },
            font: { size: 14, color: '#666666' },
            level: 1
        });
        edges.push({ from: 'project', to: 'empty', dashes: true, color: { color: '#CCCCCC' } });
    }
    
    const container = document.getElementById('mindmap-container');
    const centerX = 0, centerY = 0;
    nodes[0].x = centerX;
    nodes[0].y = centerY;
    
    // Этапы по кругу вокруг проекта
    const stageNodes = nodes.filter(n => n.level === 1);
    const stageCount = stageNodes.length;
    const radius = 320;
    stageNodes.forEach((node, index) => {
        const angle = (2 * Math.PI * index) / stageCount - Math.PI / 2;
        node.x = centerX + radius * Math.cos(angle);
        node.y = centerY + radius * Math.sin(angle);
    });
    
    // Задачи — под своим этапом
    const taskNodes = nodes.filter(n => n.level === 2);
    taskNodes.forEach((taskNode) => {
        const parentEdge = edges.find(e => e.to === taskNode.id);
        if (parentEdge) {
            const parentNode = nodes.find(n => n.id === parentEdge.from);
            if (parentNode) {
                const siblingTasks = edges.filter(e => e.from === parentNode.id).map(e => nodes.find(n => n.id === e.to)).filter(Boolean);
                const taskIndex = siblingTasks.findIndex(n => n.id === taskNode.id);
                const parentAngle = Math.atan2(parentNode.y - centerY, parentNode.x - centerX);
                const dist = 180;
                const spacing = 70;
                taskNode.x = parentNode.x + dist * Math.cos(parentAngle);
                taskNode.y = parentNode.y + dist * Math.sin(parentAngle) + (taskIndex - (siblingTasks.length - 1) / 2) * spacing;
            }
        }
    });
    
    // Создаем DataSet после установки всех позиций
    const data = {
        nodes: new vis.DataSet(nodes),
        edges: new vis.DataSet(edges)
    };
    
    const options = {
        layout: {
            hierarchical: {
                enabled: false
            }
        },
        physics: {
            enabled: false // Отключаем физику для ручного управления
        },
        interaction: {
            dragNodes: true,
            dragView: false, // Отключаем перемещение карты, только перемещение узлов
            zoomView: false, // Отключаем масштабирование колесиком, чтобы не блокировать скролл страницы
            selectConnectedEdges: false,
            hover: false, // Отключаем hover для лучшей производительности
            tooltipDelay: 200,
            multiselect: false,
            zoomSpeed: 1,
            hideEdgesOnDrag: false,
            hideEdgesOnZoom: false
        },
        configure: {
            enabled: false
        },
        nodes: {
            borderWidth: 2,
            shadow: false // Отключаем тени для лучшей производительности на тачпаде
        },
        edges: {
            arrows: {
                to: {
                    enabled: true,
                    scaleFactor: 0.8
                }
            },
            smooth: {
                type: 'curvedCW',
                roundness: 0.3
            }
        }
    };
    
    const network = new vis.Network(container, data, options);
    
    // Обработчик клика на узел
    network.on('click', function(params) {
        if (params.nodes.length > 0) {
            const nodeId = params.nodes[0];
            const node = nodes.find(n => n.id === nodeId);
            if (node && node.url) {
                window.location = node.url; // Задача — переход на просмотр
            }
        }
    });
    
    // Разрешаем скролл страницы при прокрутке колесиком над картой
    // Отслеживаем перетаскивание узлов
    let isDraggingNode = false;
    network.on('dragStart', function(params) {
        if (params.nodes.length > 0) {
            isDraggingNode = true;
        }
    });
    
    network.on('dragEnd', function(params) {
        isDraggingNode = false;
    });
    
    // Разрешаем скролл страницы, если не перетаскиваем узел
    container.addEventListener('wheel', function(event) {
        if (!isDraggingNode) {
            // Если не перетаскиваем узел, разрешаем скролл страницы
            // Не блокируем событие, чтобы страница могла скроллиться
            return;
        }
        // Если перетаскиваем узел, блокируем скролл страницы
        event.stopPropagation();
    }, { passive: true });
    
    // Кнопка экспорта в PNG
    const exportPngBtn = document.getElementById('export-png-btn');
    
    exportPngBtn.addEventListener('click', function() {
        exportPngBtn.disabled = true;
        exportPngBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Экспорт...</span>';
        
        // Подгоняем карту для экспорта
        network.fit({
            animation: false
        });
        
        // Ждем немного для завершения рендеринга
        setTimeout(function() {
            // Используем html2canvas для создания изображения
            if (typeof html2canvas !== 'undefined') {
                html2canvas(container, {
                    backgroundColor: '#fafafa',
                    scale: 2,
                    useCORS: true,
                    logging: false
                }).then(function(canvas) {
                    // Создаем ссылку для скачивания PNG
                    const link = document.createElement('a');
                    link.download = 'mind-map-' + <?= json_encode(preg_replace('/[^a-zA-Z0-9_-]/', '_', $model->title), JSON_UNESCAPED_UNICODE) ?> + '.png';
                    link.href = canvas.toDataURL('image/png');
                    link.click();
                    
                    exportPngBtn.disabled = false;
                    exportPngBtn.innerHTML = '<i class="fas fa-image"></i> <span>Экспорт в PNG</span>';
                }).catch(function(error) {
                    console.error('Ошибка при экспорте:', error);
                    alert('Ошибка при экспорте в PNG. Попробуйте еще раз.');
                    exportPngBtn.disabled = false;
                    exportPngBtn.innerHTML = '<i class="fas fa-image"></i> <span>Экспорт в PNG</span>';
                });
            } else {
                alert('Ошибка: библиотека html2canvas не загружена');
                exportPngBtn.disabled = false;
                exportPngBtn.innerHTML = '<i class="fas fa-image"></i> <span>Экспорт в PNG</span>';
            }
        }, 500);
    });
    
    // Кнопка экспорта в PDF
    const exportPdfBtn = document.getElementById('export-pdf-btn');
    
    exportPdfBtn.addEventListener('click', function() {
        exportPdfBtn.disabled = true;
        exportPdfBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Экспорт...</span>';
        
        // Подгоняем карту для экспорта
        network.fit({
            animation: false
        });
        
        // Ждем немного для завершения рендеринга
        setTimeout(function() {
            // Используем html2canvas для создания изображения
            if (typeof html2canvas !== 'undefined') {
                html2canvas(container, {
                    backgroundColor: '#fafafa',
                    scale: 2,
                    useCORS: true,
                    logging: false
                }).then(function(canvas) {
                    const imgData = canvas.toDataURL('image/png');
                    const { jsPDF } = window.jspdf;
                    const pdf = new jsPDF({
                        orientation: 'landscape',
                        unit: 'mm',
                        format: 'a4'
                    });
                    
                    const pdfWidth = pdf.internal.pageSize.getWidth();
                    const pdfHeight = pdf.internal.pageSize.getHeight();
                    const imgWidth = canvas.width;
                    const imgHeight = canvas.height;
                    const ratio = Math.min(pdfWidth / imgWidth, pdfHeight / imgHeight);
                    const width = imgWidth * ratio;
                    const height = imgHeight * ratio;
                    const x = (pdfWidth - width) / 2;
                    const y = (pdfHeight - height) / 2;
                    
                    pdf.addImage(imgData, 'PNG', x, y, width, height);
                    pdf.save('mind-map-' + <?= json_encode(preg_replace('/[^a-zA-Z0-9_-]/', '_', $model->title), JSON_UNESCAPED_UNICODE) ?> + '.pdf');
                    
                    exportPdfBtn.disabled = false;
                    exportPdfBtn.innerHTML = '<i class="fas fa-file-pdf"></i> <span>Экспорт в PDF</span>';
                }).catch(function(error) {
                    console.error('Ошибка при экспорте:', error);
                    alert('Ошибка при экспорте в PDF. Попробуйте еще раз.');
                    exportPdfBtn.disabled = false;
                    exportPdfBtn.innerHTML = '<i class="fas fa-file-pdf"></i> <span>Экспорт в PDF</span>';
                });
            } else {
                alert('Ошибка: библиотека html2canvas не загружена');
                exportPdfBtn.disabled = false;
                exportPdfBtn.innerHTML = '<i class="fas fa-file-pdf"></i> <span>Экспорт в PDF</span>';
            }
        }, 500);
    });
    
    // Применяем фит после небольшой задержки для корректного отображения
    setTimeout(function() {
        network.fit({
            animation: {
                duration: 500,
                easingFunction: 'easeInOutQuad'
            }
        });
    }, 100);
    
    // Адаптация размера при изменении окна
    window.addEventListener('resize', function() {
        network.fit({
            animation: false
        });
    });
    
    // Применяем фит после небольшой задержки для корректного отображения
    setTimeout(function() {
        network.fit({
            animation: {
                duration: 500,
                easingFunction: 'easeInOutQuad'
            }
        });
    }, 100);
    
    // Перетаскивание карты работает автоматически через dragView: true
});
</script>

<style>
.mind-map-view {
    padding: 0;
}

#mindmap-container {
    background: #fafafa;
    cursor: default;
    touch-action: pan-x pan-y pinch-zoom;
    -webkit-overflow-scrolling: touch;
    will-change: transform;
    pointer-events: auto;
    overflow: visible;
}

/* Разрешаем скролл страницы при наведении на карту */
#mindmap-container canvas {
    pointer-events: auto;
}

/* Когда курсор не на узле, разрешаем скролл */
.vis-network {
    pointer-events: auto;
}

/* Стили для улучшения отображения */
.vis-network {
    outline: none;
}

.vis-network canvas {
    outline: none;
    cursor: grab;
}

.vis-network canvas:active {
    cursor: grabbing;
}
</style>

