<?php
include_once dirname(dirname(dirname(__FILE__))).'/inmp.vw.php';

$view['params']['layout-body'] = '/mp/matter/article/frame';
$view['params']['menu'] = '/page/mp/matter';
$view['params']['css'] = array(array('/mp/matter/article','main'));
$view['params']['global_js'] = array('tinymce/tinymce.min','resumable','matters-xxt');
$view['params']['angular-modules'] = "'channel.matter.mp','matters.xxt'"; 
$view['params']['js'] = array(array('/mp','channel'), array('/mp/matter/article','main'));
