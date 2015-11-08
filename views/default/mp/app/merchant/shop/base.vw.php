<?php
include_once dirname(dirname(dirname(dirname(__FILE__)))) . '/inmp.vw.php';

$view['params']['menu'] = '/rest/mp/app';
$view['params']['angular-modules'] = "'ngRoute','ui.bootstrap','matters.xxt'";
$view['params']['layout-body'] = '/mp/app/merchant/shop/base';
$view['params']['global_js'] = array('matters-xxt');
$view['params']['css'] = array(array('/mp/app/merchant/shop', 'base', true));
$view['params']['js'] = array(array('/mp/mpaccount', 'mp', true), array('/mp/app/merchant/shop', 'base', true));