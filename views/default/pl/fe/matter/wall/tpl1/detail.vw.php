<?php
include_once dirname(dirname(dirname(__FILE__))) . '/inmp.vw.php';

$view['params']['menu'] = '/rest/mp/app';
$view['params']['layout-body'] = '/mp/app/wall/detail';
$view['params']['angular-modules'] = "'ngRoute','ui.xxt','ui.bootstrap'";
$view['params']['global_js'] = array('xxt.ui', true);
$view['params']['css'] = array(array('/mp/app/wall', 'detail', true));
$view['params']['js'] = array(array('/mp/app/wall', 'detail', true));