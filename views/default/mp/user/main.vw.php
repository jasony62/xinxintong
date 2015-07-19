<?php
include_once dirname(dirname(__FILE__)).'/inmp.vw.php';

$view['params']['angular-modules'] = "'ui.bootstrap'";
$view['params']['js'] = array(array('/mp/user', 'user'));
$view['params']['css'] = array(array('/mp/user', 'user'));
$view['params']['layout-body'] = '/mp/user/user';
$view['params']['menu'] = '/page/mp/fans';
