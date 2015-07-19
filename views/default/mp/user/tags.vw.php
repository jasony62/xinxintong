<?php
include_once dirname(__FILE__).'/wrap.vw.php';

$view['params']['js'] = array(array('/mp/user', 'tags'));
$view['params']['subView'] = '/mp/user/tags';
