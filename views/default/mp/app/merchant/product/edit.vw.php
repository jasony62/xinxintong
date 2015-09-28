<?php
include_once dirname(dirname(dirname(dirname(__FILE__)))) . '/inmp.vw.php';

$view['params']['menu'] = '/rest/mp/app';
$view['params']['global_js'] = array(array('matters-xxt', true));
$view['params']['angular-modules'] = "'ngRoute','ui.bootstrap','matters.xxt'";
$view['params']['layout-body'] = '/mp/app/merchant/product/edit';
//$view['params']['css'] = array(array('/mp/app/merchant/product', 'edit', true));
$view['params']['js'] = array(array('/mp/app/merchant/product', 'edit', true));