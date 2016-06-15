<?php
include_once dirname(dirname(__FILE__)) . '/wrap.vw.php';

$view['params']['angular-modules'] = "'ui.bootstrap','ui.xxt'";
$view['params']['global_js'] = array('xxt.ui', true);
$view['params']['js'] = array(array('/mp/user/send', 'main', true));
$view['params']['subView'] = '/mp/user/send/main';