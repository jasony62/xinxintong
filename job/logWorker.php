<?php
include_once '../cus/app.php';

date_default_timezone_set('Asia/Shanghai');
ini_set('default_charset', 'utf-8');
putenv("QUEUE=default");
putenv("VVERBOSE=1");
putenv('REDIS_BACKEND=' . TMS_PHP_RESQUE_REDIS);
define('TMS_APP_DIR', dirname(dirname(__FILE__)));
// 调用任务
include_once 'autoload.php';
include_once '../vendor/chrisboulton/php-resque/resque.php';