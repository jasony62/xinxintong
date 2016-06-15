<?php
include_once dirname(dirname(__FILE__)) . '/inmp.vw.php';

$view['params']['global_js'] = array('xxt.ui', true);
$view['params']['angular-modules'] = "'ngRoute','ui.xxt'";
$view['params']['css'] = array(array('/mp/mission', 'detail', true));
$view['params']['js'] = array(array('/mp/mission', 'detail', true));
$view['params']['layout-body'] = '/mp/mission/detail';