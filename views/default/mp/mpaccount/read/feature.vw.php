<?php
include_once dirname(dirname(__FILE__)).'/wrap.vw.php';

$view['params']['js'][] = array('/mp/mpaccount','feature');
$view['params']['sub-view'] = 'read/feature';
