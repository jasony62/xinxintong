<?php
include_once dirname(dirname(dirname(__FILE__))).'/inmp.vw.php';

$view['params']['menu'] = '/page/mp/activity/enroll';
$view['params']['layout-body'] = '/mp/activity/discuss/detail';
$view['params']['angular-modules'] = "'matters.xxt','ui.bootstrap'";
$view['params']['global_js'] = array('matters-xxt');
$view['params']['js'] = array(array('/mp/activity/discuss','detail'));
