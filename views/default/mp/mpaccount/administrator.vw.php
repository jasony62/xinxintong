<?php
include_once dirname(__FILE__) . '/wrap.vw.php';

$view['params']['js'][] = array('/mp/mpaccount', 'administrator');
$view['params']['sub-view'] = 'administrator';