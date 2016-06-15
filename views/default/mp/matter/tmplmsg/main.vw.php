<?php
include_once dirname(dirname(dirname(__FILE__))) . '/inmp.vw.php';

$view['params']['layout-body'] = '/mp/matter/tmplmsg/frame';
$view['params']['menu'] = '/page/mp/matter';
$view['params']['css'] = array(array('/mp/matter/tmplmsg', 'main'));
$view['params']['global_js'] = array('xxt.ui', true);
$view['params']['js'] = array(array('/mp/matter/tmplmsg', 'main'));
$view['params']['angular-modules'] = "'ui.xxt'";
