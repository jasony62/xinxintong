<?php
include_once dirname(dirname(__FILE__)) . '/inapp.vw.php';

$view['params']['menu'] = '/rest/mp/app';
$view['params']['layout-body'] = '/mp/app/contribute/detail';
$view['params']['global_js'] = array('matters-xxt', true);
$view['params']['angular-modules'] = "'ngRoute','channel.matter.mp','matters.xxt','ui.bootstrap'";
$view['params']['css'] = array(array('/mp/app/contribute', 'detail', true));
$view['params']['js'] = array(array('/mp', 'channel'), array('/mp/app/contribute', 'detail', true));
$view['params']['tips'] = '<p>仅限认证用户参与投稿。</p>';