<?php
include_once dirname(__FILE__) . '/common.vw.php';

$view['params']['global_js'] = array('xxt.ui', true);
$view['params']['angular-modules'] = "'ui.xxt'";
$view['params']['js'] = array(array('/mp/matter', 'texts', true));
$view['params']['msg_type'] = 'texts';