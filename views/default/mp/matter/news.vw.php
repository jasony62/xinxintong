<?php
include_once dirname(__FILE__) . '/common.vw.php';

$view['params']['global_js'] = array('matters-xxt');
$view['params']['angular-modules'] = "'matters.xxt'";
$view['params']['js'] = array(array('/mp/matter','news'));
$view['params']['msg_type'] = 'news';
$view['params']['tips'] = '<p>可以通过鼠标拖拽改变图文列表中图文的顺序。</p>';
