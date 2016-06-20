<?php
include_once dirname(dirname(dirname(__FILE__))) . '/inmp.vw.php';

$view['params']['menu'] = '/rest/mp/app';

$view['params']['global_js'] = array('tinymce/tinymce.min', 'matters-xxt', 'angular-sanitize.min', true);
$view['params']['angular-modules'] = "'ngRoute','ngSanitize','channel.matter.mp','matters.xxt','ui.bootstrap'";
$view['params']['css'] = array(array('/mp/app/enroll', 'detail', true));
$view['params']['js'] = array(array('/mp', 'channel'), array('/mp/app/enroll', 'detail', true));
$view['params']['layout-body'] = '/mp/app/enroll/detail';