<?php
include_once dirname(__FILE__) . '/inmp.vw.php';

$view['params']['layout-body'] = '/mp/main';
$view['params']['angular-modules'] = "'ngRoute','matters.xxt'";
$view['params']['global_js'] = array('matters-xxt');
$view['params']['css'][] = array('/mp', 'main', true);
$view['params']['js'][] = array('/mp', 'main', true);