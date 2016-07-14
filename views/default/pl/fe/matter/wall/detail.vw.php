<?php
include_once dirname(__FILE__).'/inmp.vw.php';

$view['params']['menu'] = '/rest/mp/app';
$view['params']['layout-body'] = '/pl/fe/matter/wall/detail';
$view['params']['angular-modules'] = "'ngRoute','ui.xxt','ui.bootstrap'";
$view['params']['global_js'] = array('xxt.ui', true);
$view['params']['css'] = array(array('/pl/fe/matter/wall', 'detail', true));
$view['params']['js'] = array(array('/pl/fe/matter/wall', 'detail', true));