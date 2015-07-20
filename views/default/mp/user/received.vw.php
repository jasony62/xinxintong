<?php
include_once dirname(__FILE__).'/wrap.vw.php';

$view['params']['angular-modules'] = "'ui.bootstrap'";
$view['params']['js'] = array(array('/mp/user', 'received'));
$view['params']['subView'] = '/mp/user/received';
