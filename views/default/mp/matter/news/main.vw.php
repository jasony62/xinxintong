<?php
include_once dirname(dirname(dirname(__FILE__))) . '/inmp.vw.php';

$view['params']['layout-body'] = '/mp/matter/news/frame';
$view['params']['menu'] = '/page/mp/matter';
$view['params']['css'] = array(array('/mp/matter/news', 'main', true));
$view['params']['global_js'] = array(array('xxt.ui', true));
$view['params']['angular-modules'] = "'ngRoute','ui.xxt'";
$view['params']['js'] = array(array('/mp/matter/news', 'main', true));
