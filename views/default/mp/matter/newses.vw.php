<?php
include_once dirname(__FILE__) . '/common.vw.php';

$view['params']['global_js'] = array('matters-xxt', true);
$view['params']['angular-modules'] = "'matters.xxt'";
$view['params']['css'] = array(array('/mp/matter', 'newses', true));
$view['params']['js'] = array(array('/mp/matter', 'newses', true));
$view['params']['msg_type'] = 'newses';