<?php
/** Yii1 partial: Breadcrumbs */

if (empty($this->breadcrumbs)) {
    return;
}

$this->widget('zii.widgets.CBreadcrumbs', array(
    'links' => $this->breadcrumbs,
    'htmlOptions' => array('class' => 'breadcrumb'),
));


