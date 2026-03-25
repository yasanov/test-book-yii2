<?php

require __DIR__ . '/../vendor/autoload.php';

// Load environment variables from .env file
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

$yiiDebug = $_ENV['YII_DEBUG'] ?? getenv('YII_DEBUG');
$yiiEnv = $_ENV['YII_ENV'] ?? getenv('YII_ENV');

defined('YII_DEBUG') or define('YII_DEBUG', filter_var($yiiDebug ?? false, FILTER_VALIDATE_BOOL));
defined('YII_ENV') or define('YII_ENV', is_string($yiiEnv) && $yiiEnv !== '' ? $yiiEnv : 'prod');

require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../config/web.php';

(new yii\web\Application($config))->run();
