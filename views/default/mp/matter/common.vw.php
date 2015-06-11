<?php
include_once dirname(dirname(__FILE__)) . '/inmp.vw.php';

$view['params']['layout-body'] = '/mp/matter/main';
$view['params']['menu'] = '/rest/mp/matter';
$view['params']['css'] = array(array('/mp/matter', 'common'));
$view['params']['angular-modules'] = "'ui.bootstrap'";
