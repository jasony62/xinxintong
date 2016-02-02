<?php
include_once dirname(__FILE__) . '/common.vw.php';

$view['params']['css'] = array(array('/mp/matter', 'links'));
$view['params']['js'] = array(array('/mp/matter', 'links', true));
$view['params']['msg_type'] = 'links';