<?php
include_once dirname(dirname(__FILE__)).'/inaction.vw.php';

$view['params']['action-view'] = '/mp/activity/enroll/main';
$view['params']['css'] = array(array('/mp/activity/enroll', 'main'));
$view['params']['angular-modules'] = "'ui.bootstrap'";
$view['params']['js'] = array(array('/mp/activity/enroll', 'main'));
