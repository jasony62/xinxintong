<?php

/**
 * local timezone
 */
date_default_timezone_set('Asia/Shanghai');

$rst = new stdClass;
$rst->err_code = 0;
$rst->err_msg = 'success';
$rst->data = time();

header('Content-type: application/json');
echo json_encode($rst);