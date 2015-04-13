<?php
include_once dirname(dirname(__FILE__)).'/inmp.vw.php';

$view['params']['menu'] = '/page/mp/activity/enroll';
$view['params']['layout-body'] = '/mp/activity/inaction.tpl.htm';
$view['params']['global_js'] = array('matters-xxt');
$view['params']['angular-modules'] = "'matters.xxt'";
