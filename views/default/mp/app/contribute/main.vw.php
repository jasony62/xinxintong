<?php
include_once dirname(dirname(__FILE__)).'/inapp.vw.php';

$view['params']['app-view'] = '/mp/app/contribute';
$view['params']['angular-modules'] = "'ui.bootstrap'";
$view['params']['css'] = array(array('/mp/app/contribute','main'));
$view['params']['js'] = array(array('/mp/app/contribute','main'));