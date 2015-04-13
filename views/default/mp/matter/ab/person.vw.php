<?php
include_once dirname(dirname(dirname(__FILE__))).'/inmp.vw.php';

$view['params']['layout-body'] = '/mp/matter/ab/person';
$view['params']['menu'] = '/page/mp/matter';
$view['params']['css'] = array(array('/mp/matter/ab','person'));
$view['params']['js'] = array(array('/mp/matter/ab','person','deptSelector'));
$view['params']['angular-modules'] = "'ui.bootstrap'";
