<?php
include_once dirname(__FILE__) . '/common.vw.php';

$view['params']['global_js'] = array('ui-tms', 'xxt.ui');
$view['params']['angular-modules'] = "'ui.xxt'";
$view['params']['js'] = array(array('/mp/matter', 'inner', true));
$view['params']['msg_type'] = 'inner';