<?php
include_once dirname(dirname(__FILE__)).'/inmp.vw.php';

$view['params']['css'] = array(array('/mp/bbs', 'main'));
$view['params']['angular-modules'] = "'ngRoute'";
$view['params']['js'] = array(array('/mp/bbs', 'main'));
$view['params']['layout-body'] = '/mp/bbs/main';
$view['params']['menu'] = '/page/mp/bbs';
