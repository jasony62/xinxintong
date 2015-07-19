<?php
include_once dirname(dirname(__FILE__)).'/inapp.vw.php';

TPL::assign('entryURL', 'http://'.$_SERVER['HTTP_HOST'].'/rest/app/wall?mpid='.$_SESSION['mpid']);

$view['params']['app-view'] = '/mp/app/wall';
$view['params']['css'] = array(array('/mp/app/wall', 'main'));
$view['params']['js'] = array(array('/mp/app/wall', 'main'));
