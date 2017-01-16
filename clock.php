<?php

/**
 * local timezone
 */
date_default_timezone_set('Asia/Shanghai');
header('Content-Type: text/html; charset=utf-8');

$rst = new stdClass;
$rst->err_code = 0;
$rst->err_msg = 'success';
$rst->data = time();

echo json_encode($rst);