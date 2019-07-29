<?php
include_once dirname(__FILE__) . '/config.php';

$config = new \stdClass;
$config->noHookMaxTime = TMS_APP_NOHOOK_MAXTIME;
$config->fileMaxSize = TMS_UPLOAD_FILE_MAXSIZE;
$config->fileContentTypeWhite = TMS_UPLOAD_FILE_CONTENTTYPE_WHITE;

$config = json_encode($config);

header('Content-type: application/json');
header('Cache-Control: no-cache');
echo $config;