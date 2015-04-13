<?php
include_once dirname(dirname(dirname(__FILE__))).'/inmp.vw.php';

$view['params']['layout-body'] = '/mp/matter/link/frame';
$view['params']['menu'] = '/page/mp/matter';
$view['params']['css'] = array(array('/mp/matter/link','main'));
$view['params']['global_js'] = array('matters-xxt');
$view['params']['js'] = array(array('/mp/matter','common'), array('/mp/matter/link','main'));
$view['params']['angular-modules'] = "'common.matter.mp','matters.xxt'"; 
