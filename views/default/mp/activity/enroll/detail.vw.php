<?php
include_once dirname(dirname(dirname(__FILE__))).'/inmp.vw.php';

$view['params']['global_js'] = array('tinymce/tinymce.min','matters-xxt');
$view['params']['angular-modules'] = "'matters.xxt','ui.bootstrap'";
$view['params']['css'] = array(array('/mp/activity/enroll','detail'));
$view['params']['js'] = array(array('/mp/activity/enroll','detail'));
$view['params']['layout-body'] = '/mp/activity/enroll/detail';
$view['params']['menu'] = '/page/mp/activity/enroll';
