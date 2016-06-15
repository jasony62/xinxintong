<?php
include_once dirname(__FILE__) . '/inmp.vw.php';

$view['params']['layout-body'] = '/mp/main';
$view['params']['angular-modules'] = "'ngRoute','ui.xxt'";
$view['params']['global_js'] = array('xxt.ui');
$view['params']['css'][] = array('/mp', 'main', true);
$view['params']['js'][] = array('/mp', 'main', true);