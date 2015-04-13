<?php
include_once(dirname(dirname(__FILE__)).'/inaction.vw.php');

TPL::assign('entryURL', 'http://'.$_SERVER['HTTP_HOST'].'/rest/activity/discuss?mpid='.$_SESSION['mpid']);

$view['params']['action-view'] = '/mp/activity/discuss/main';
$view['params']['css'] = array(array('/mp/activity/discuss', 'main'));
$view['params']['js'] = array(array('/mp/activity/discuss', 'main'));
