<?php
include_once dirname(dirname(__FILE__)) . '/inmp.vw.php';

$view['params']['angular-modules'] = "'matters.xxt'";
$view['params']['global_js'] = array('matters-xxt');
$view['params']['js'][] = array('/mp/mpaccount', 'main');
$view['params']['layout-body'] = '/mp/mpaccount/wrap';
$view['params']['menu'] = '/rest/mp/mpaccount';