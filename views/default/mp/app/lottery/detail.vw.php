<?php
include_once dirname(dirname(dirname(__FILE__))).'/inmp.vw.php';

$view['params']['menu'] = '/rest/mp/app';
$view['params']['global_js'] = array('matters-xxt', true);
$view['params']['angular-modules'] = "'ngRoute','ui.bootstrap','matters.xxt'";
$view['params']['layout-body'] = '/mp/app/lottery/detail';
$view['params']['css'] = array(array('/mp/app/lottery', 'detail', true));
$view['params']['js'] = array(array('/mp/app/lottery', 'detail', true));
