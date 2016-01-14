<?php
include_once dirname(__FILE__) . '/wrap.vw.php';

$view['params']['css'] = array(array('/mp/user', 'departments'));
$view['params']['js'] = array(array('/mp/user', 'departments', true));
$view['params']['subView'] = '/mp/user/departments';