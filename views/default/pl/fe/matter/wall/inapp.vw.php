<?php
include_once dirname(__FILE__) . '/inmp.vw.php';

$view['params']['menu'] = '/rest/mp/app';
$view['params']['layout-body'] = '/pl/fe/matter/wall/inapp.tpl.htm';
$view['params']['global_js'] = array('xxt.ui', true);
$view['params']['angular-modules'] = "'ui.xxt'";
