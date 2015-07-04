<?php
include_once dirname(dirname(__FILE__)).'/inmp.vw.php';

$view['params']['menu'] = '/rest/mp/app';
$view['params']['layout-body'] = '/mp/app/inapp.tpl.htm';
$view['params']['global_js'] = array('matters-xxt', true);
$view['params']['angular-modules'] = "'matters.xxt'";
