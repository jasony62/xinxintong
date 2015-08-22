<?php
include_once dirname(dirname(__FILE__)) . '/inmp.vw.php';

$view['params']['css'] = array(array('/mp/analyze', 'main'));
$view['params']['js'] = array(array('/mp/analyze', 'main', true));
$view['params']['angular-modules'] = "'ngRoute','ui.bootstrap'";
$view['params']['layout-body'] = '/mp/analyze/main';
$view['params']['menu'] = '/page/mp/analyze';
