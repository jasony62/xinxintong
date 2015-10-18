<?php
include_once dirname(dirname(__FILE__)) . '/inapp.vw.php';

$view['params']['app-view'] = '/mp/app/merchant';
$view['params']['css'] = array(array('/mp/app/merchant', 'main'));
$view['params']['js'] = array(array('/mp/app/merchant', 'main'));