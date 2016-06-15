<?php
include_once dirname(__FILE__) . '/common.vw.php';

$view['params']['global_js'] = array('xxt.ui', true);
$view['params']['angular-modules'] = "'ui.xxt'";
$view['params']['css'] = array(array('/mp/matter', 'newses', true));
$view['params']['js'] = array(array('/mp/matter', 'newses', true));
$view['params']['msg_type'] = 'newses';