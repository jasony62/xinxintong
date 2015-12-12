<?php
include_once dirname(dirname(__FILE__)) . '/inapp.vw.php';

$view['params']['app-view'] = '/mp/app/enroll';
$view['params']['css'] = array(array('/mp/app/enroll', 'main'));
$view['params']['angular-modules'] = "'ui.bootstrap'";
$view['params']['js'] = array(array('/mp/app/enroll', 'main'));