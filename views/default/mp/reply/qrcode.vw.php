<?php
include_once dirname(__FILE__) . '/common.vw.php';

$view['params']['angular-modules'] = "'ui.xxt'";
$view['params']['js'][] = array('/mp/reply', 'qrcode');
$view['params']['msg_type'] = 'qrcode';
