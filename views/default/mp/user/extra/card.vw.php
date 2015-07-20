<?php
include_once dirname(dirname(__FILE__)).'/inmp.vw.php';

$cardurl = 'http://'.$_SERVER['HTTP_HOST'].'/rest/member/card?mpid='.$_SESSION['mpid'];
$view['params']['js'] = array(array('/mp/member', 'card'));
$view['params']['layout-body'] = '/mp/member/main';
$view['params']['menu'] = '/page/mp/member';
$view['params']['subbody'] = '/mp/member/card';
$view['params']['subpage'] = '/page/mp/member/card';
$view['params']['cardurl'] = $cardurl;
