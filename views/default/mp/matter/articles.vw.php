<?php
include_once dirname(__FILE__) . '/common.vw.php';

$view['params']['global_js'] = array(array('resumable'));
$view['params']['css'] = array(array('/mp/matter', 'articles', true));
$view['params']['js'] = array(array('/mp/matter', 'articles', true));
$view['params']['msg_type'] = 'articles';
