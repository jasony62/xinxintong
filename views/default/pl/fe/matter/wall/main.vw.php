<?php
include_once dirname(__FILE__).'/inapp.vw.php';

TPL::assign('entryURL', 'http://'.$_SERVER['HTTP_HOST'].'/rest/app/wall?mpid='.$_SESSION['mpid']);

$view['params']['app-view'] = '/pl/fe/matter/wall';
$view['params']['css'] = array(array('/pl/fe/matter/wall', 'main'));
$view['params']['js'] = array(array('/pl/fe/matter/wall', 'main'));
