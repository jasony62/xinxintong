<?php
include_once dirname(__FILE__) . '/wrap.vw.php';

$view['params']['js'][] = array('/mp/mpaccount', 'syslog');
$view['params']['sub-view'] = 'syslog';