<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();


$config = [
    'id' => 'basic',
    'name' => 'KU Track',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => '0zdNwcnW5oY-8IOGzTgHPDZKoRIYOil_',
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mongodb' => [
            'class' => '\yii\mongodb\Connection',
            'dsn' => $_ENV['MONGO_DSN'],
            'defaultDatabaseName' => $_ENV['MONGO_DB'],
        ],
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@app/mail',
            // send all mails to a file by default.
            'useFileTransport' => true,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                // Правила для админки
                'admin' => 'admin/default/index',
                'admin/user/<id:[a-f0-9]{24}>' => 'admin/user/view',
                'admin/user/<action:\w+>' => 'admin/user/<action>',
                'admin/<controller:\w+>/<id:[a-f0-9]{24}>' => 'admin/<controller>/view',
                'admin/<controller:\w+>/<action:\w+>' => 'admin/<controller>/<action>',
                // Правила для проектов
                'project/<id:[a-f0-9]{24}>/kanban' => 'project/kanban',
                'project/<id:[a-f0-9]{24}>' => 'project/view',
                'project/<action:\w+>' => 'project/<action>',
                'project/<action:\w+>/<id:[a-f0-9]{24}>' => 'project/<action>',
                // Правила для глобального проекта
                'global-project/<id:[a-f0-9]{24}>/kanban' => 'global-project/kanban',
                'global-project/<id:[a-f0-9]{24}>' => 'global-project/view',
                'global-project/<action:\w+>' => 'global-project/<action>',
                'global-project/<action:\w+>/<id:[a-f0-9]{24}>' => 'global-project/<action>',
                // Правила для ТЗ
                'project-spec/toggle-milestone/<project_id:[a-f0-9]{24}>' => 'project-spec/toggle-milestone',
                'project-spec/<action:\w+>/<project_id:[a-f0-9]{24}>' => 'project-spec/<action>',
                // Правила для задач
                'task/create/<project_id:[a-f0-9]{24}>' => 'task/create',
                'task/<id:[a-f0-9]{24}>/update' => 'task/update',
                'task/<id:[a-f0-9]{24}>/delete' => 'task/delete',
                'task/<id:[a-f0-9]{24}>/change-status' => 'task/change-status',
                'task/<id:[a-f0-9]{24}>' => 'task/view',
            ],
        ],
    ],
    'modules' => [
        'admin' => [
            'class' => 'app\modules\admin\Module',
        ],
    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}

return $config;

