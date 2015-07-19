<?php
include_once dirname(__FILE__).'/wrap.vw.php';

$view['params']['global_js'] = array('matters-xxt');
$view['params']['angular-modules'] = "'ui.bootstrap','matters.xxt'";
$view['params']['js'] = array(array('/mp/user','fansgroup'));
$view['params']['subView'] = '/mp/user/fansgroup';
$view['params']['tips'] = '<p>公众平台未提供删除用户分组接口。</p>';
