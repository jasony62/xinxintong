<?php
include_once dirname(__FILE__) . '/common.vw.php';

$view['params']['angular-modules'] = "'ui.tms','matters.xxt'";
$view['params']['js'][] = array('/mp/reply', 'timer', true);
$view['params']['msg_type'] = 'timer';
