<?php
include_once dirname(dirname(__FILE__)) . '/inmp.vw.php';

$view['params']['angular-modules'] = "'ui.xxt'";
$view['params']['global_js'] = array('xxt.ui');
$view['params']['js'][] = array('/mp/mpaccount', 'main');
$view['params']['layout-body'] = '/mp/mpaccount/wrap';
$view['params']['menu'] = '/rest/mp/mpaccount';