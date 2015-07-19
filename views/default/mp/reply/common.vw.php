<?php
include_once dirname(dirname(__FILE__)).'/inmp.vw.php';
/**
 * default view is menu.
 */
$view['params']['global_js'] = array('matters-xxt');
$view['params']['css'][] = array('/mp/reply','common');
$view['params']['layout-body'] = '/mp/reply/main';
$view['params']['menu'] = '/rest/mp/call';
