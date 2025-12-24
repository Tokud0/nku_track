<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\models\Project;

/** @var yii\web\View $this */
/** @var app\models\Project $model */
/** @var array $tasks */

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
                        Визуализация структуры проекта, целей и задач
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
    // Данные для mind map
    const projectData = {
        id: 'project',
        title: <?= json_encode($model->title, JSON_UNESCAPED_UNICODE) ?>,
        description: <?= json_encode($model->description ?? '', JSON_UNESCAPED_UNICODE) ?>
    };
    
    const tasks = <?= json_encode($tasks, JSON_UNESCAPED_UNICODE) ?>;
    
    // Создаем узлы и связи
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
            highlight: {
                background: '#5BA0F2',
                border: '#2E5C8A'
            }
        },
        font: {
            size: 20,
            color: '#FFFFFF',
            face: 'Arial',
            bold: true
        },
        widthConstraint: {
            maximum: 300
        },
        level: 0
    });
    
    // Узлы задач проекта (квадратики)
    tasks.forEach((task, index) => {
        const taskId = 'task_' + task.id;
        nodes.push({
            id: taskId,
            label: task.title,
            title: task.description || task.title,
            shape: 'box',
            color: {
                background: '#50C878',
                border: '#2E8B57',
                highlight: {
                    background: '#60D888',
                    border: '#2E8B57'
                }
            },
            font: {
                size: 16,
                color: '#FFFFFF',
                face: 'Arial',
                bold: true
            },
            widthConstraint: {
                maximum: 250
            },
            level: 1
        });
        
        // Связь от проекта к задаче
        edges.push({
            from: 'project',
            to: taskId,
            color: {
                color: '#50C878',
                highlight: '#60D888'
            },
            width: 3
        });
        
        // Узлы подзадач задачи (квадратики) - показываем только если есть подзадачи
        if (task.subtasks && task.subtasks.length > 0) {
            task.subtasks.forEach((subtask, subtaskIndex) => {
                const subtaskId = 'subtask_' + task.id + '_' + subtaskIndex;
                const isCompleted = subtask.completed === true;
                
                nodes.push({
                    id: subtaskId,
                    label: subtask.text,
                    title: 'Подзадача из задачи: ' + task.title + (isCompleted ? ' (Выполнена)' : ''),
                    shape: 'box',
                    color: {
                        background: isCompleted ? '#90EE90' : '#FFB84D',
                        border: isCompleted ? '#32CD32' : '#FF8C00',
                        highlight: {
                            background: isCompleted ? '#98FB98' : '#FFC85D',
                            border: isCompleted ? '#32CD32' : '#FF8C00'
                        }
                    },
                    font: {
                        size: 14,
                        color: '#000000',
                        face: 'Arial',
                        strikethrough: isCompleted
                    },
                    widthConstraint: {
                        maximum: 200
                    },
                    level: 2
                });
                
                // Связь от задачи к подзадаче
                edges.push({
                    from: taskId,
                    to: subtaskId,
                    color: {
                        color: isCompleted ? '#90EE90' : '#FFB84D',
                        highlight: isCompleted ? '#98FB98' : '#FFC85D'
                    },
                    width: 2,
                    dashes: isCompleted // Пунктирная линия для выполненных
                });
            });
        }
    });
    
    // Если нет данных
    if (nodes.length === 1 && edges.length === 0) {
        nodes.push({
            id: 'empty',
            label: 'Нет данных для отображения\n\nСоздайте задачи проекта\nи добавьте подзадачи в todo-лист',
            shape: 'box',
            color: {
                background: '#E0E0E0',
                border: '#BDBDBD'
            },
            font: {
                size: 14,
                color: '#666666'
            },
            level: 1
        });
        
        edges.push({
            from: 'project',
            to: 'empty',
            dashes: true,
            color: {
                color: '#CCCCCC'
            }
        });
    }
    
    // Создаем сеть
    const container = document.getElementById('mindmap-container');
    
    // Располагаем проект в центре
    const centerX = 0;
    const centerY = 0;
    nodes[0].x = centerX;
    nodes[0].y = centerY;
    
    // Располагаем задачи по кругу вокруг проекта
    const taskNodes = nodes.filter(n => n.level === 1);
    const taskCount = taskNodes.length;
    const radius = 300; // Радиус круга для задач
    
    taskNodes.forEach((node, index) => {
        const angle = (2 * Math.PI * index) / taskCount - Math.PI / 2; // Начинаем сверху
        node.x = centerX + radius * Math.cos(angle);
        node.y = centerY + radius * Math.sin(angle);
    });
    
    // Располагаем подзадачи в ряд от своих задач
    const subtaskNodes = nodes.filter(n => n.level === 2);
    subtaskNodes.forEach((subtaskNode) => {
        // Находим родительскую задачу
        const parentEdge = edges.find(e => e.to === subtaskNode.id);
        if (parentEdge) {
            const parentNode = nodes.find(n => n.id === parentEdge.from);
            if (parentNode) {
                // Находим все подзадачи этой задачи
                const siblingSubtasks = edges
                    .filter(e => e.from === parentNode.id)
                    .map(e => nodes.find(n => n.id === e.to))
                    .filter(n => n && n.level === 2)
                    .sort((a, b) => {
                        // Сортируем по индексу для правильного порядка
                        const aMatch = a.id.match(/_(\d+)$/);
                        const bMatch = b.id.match(/_(\d+)$/);
                        const aIndex = aMatch ? parseInt(aMatch[1]) : 0;
                        const bIndex = bMatch ? parseInt(bMatch[1]) : 0;
                        return aIndex - bIndex;
                    });
                
                const subtaskIndex = siblingSubtasks.findIndex(n => n.id === subtaskNode.id);
                const subtaskCount = siblingSubtasks.length;
                const subtaskSpacing = 80; // Очень компактное расстояние между подзадачами (вертикальный список)
                
                if (subtaskCount > 0) {
                    // Вычисляем направление от центра к задаче
                    const parentAngle = Math.atan2(parentNode.y - centerY, parentNode.x - centerX);
                    
                    // Вычисляем начальную позицию списка (на расстоянии от задачи)
                    const distanceFromTask = 200; // Расстояние от задачи до начала списка подзадач
                    const listStartX = parentNode.x + distanceFromTask * Math.cos(parentAngle);
                    const listStartY = parentNode.y + distanceFromTask * Math.sin(parentAngle);
                    
                    // Располагаем подзадачи вертикальным списком (вниз от задачи)
                    // Используем перпендикулярное направление для вертикального списка
                    const perpendicularAngle = parentAngle + Math.PI / 2;
                    
                    // Располагаем подзадачи вертикально друг под другом
                    subtaskNode.x = listStartX + 0 * Math.cos(perpendicularAngle); // По горизонтали на одной линии
                    subtaskNode.y = listStartY + subtaskIndex * subtaskSpacing; // Вертикально друг под другом
                }
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
            if (node && node.title) {
                // Можно добавить модальное окно с деталями
                console.log('Clicked node:', node.title);
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

