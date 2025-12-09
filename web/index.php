<?php
// Подавляем предупреждение о deprecated методе MongoDB Cursor::getId()
// Это предупреждение из пакета yii2-mongodb и не влияет на функциональность
$oldErrorReporting = error_reporting();
error_reporting($oldErrorReporting & ~E_DEPRECATED);

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();
// comment out the following two lines when deployed to production
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../config/web.php';

(new yii\web\Application($config))->run();
