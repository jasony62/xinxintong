<?php
include_once dirname(__FILE__) . '/wrap.vw.php';

$view['params']['js'][] = array('/mp/mpaccount', 'coin');
$view['params']['sub-view'] = 'coin';