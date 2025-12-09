<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$config = [
    'id' => 'basic-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'app\commands',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
        '@tests' => '@app/tests',
    ],
    'components' => [
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'mongodb' => [
            'class' => '\yii\mongodb\Connection',
            'dsn' => $_ENV['MONGO_DSN'],
            'defaultDatabaseName' => $_ENV['MONGO_DB'],
        ],
        'log' => [
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
    ],
    'on beforeRequest' => function () {
        // Подавляем предупреждения о deprecated методах MongoDB в консоли
        error_reporting(error_reporting() & ~E_DEPRECATED);
    },
    'params' => $params,
    'controllerMap' => [
        'mongodb-migrate' => [
            'class' => 'yii\mongodb\console\controllers\MigrateController',
            'migrationPath' => '@app/migrations',
        ],
        /*
        'fixture' => [ // Fixture generation command line.
            'class' => 'yii\faker\FixtureController',
        ],
        */
    ],
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
    ];
    // configuration adjustments for 'dev' environment
    // requires version `2.1.21` of yii2-debug module
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}

return $config;
