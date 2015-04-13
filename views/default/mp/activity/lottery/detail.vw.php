<?php
include_once dirname(dirname(dirname(__FILE__))).'/inmp.vw.php';

$view['params']['menu'] = '/page/mp/activity/enroll';
$view['params']['global_js'] = array('matters-xxt');
$view['params']['angular-modules'] = "'ngRoute','ui.bootstrap','matters.xxt'";
$view['params']['layout-body'] = '/mp/activity/lottery/detail';
$view['params']['css'] = array(array('/mp/activity/lottery','detail'));
$view['params']['js'] = array(array('/mp/activity/lottery','detail'));
