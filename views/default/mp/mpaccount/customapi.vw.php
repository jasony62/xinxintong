<?php
include_once dirname(__FILE__).'/wrap.vw.php';

$view['params']['js'][] = array('/mp/mpaccount', 'mp', 'customapi');
$view['params']['sub-view'] = 'customapi';
