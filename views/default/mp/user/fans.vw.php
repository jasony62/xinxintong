<?php
include_once dirname(__FILE__).'/wrap.vw.php';

$view['params']['global_js'] = array('matters-xxt', true);
$view['params']['angular-modules'] = "'ui.bootstrap','matters.xxt'";
$view['params']['js'] = array(array('/mp/user','fans', true));
$view['params']['subView'] = '/mp/user/fans';
