<?php
include_once dirname(__FILE__) . '/common.vw.php';

$view['params']['global_js'] = array('ui-tms', 'matters-xxt');
$view['params']['angular-modules'] = "'matters.xxt'";
$view['params']['js'] = array(array('/mp/matter', 'inners', true));
$view['params']['msg_type'] = 'inners';