<?php
include_once dirname(dirname(__FILE__)).'/inmp.vw.php';

$view['params']['layout-body'] = '/mp/member/main';
$view['params']['menu'] = '/page/mp/member';
$view['params']['subbody'] = '/mp/member/checkin';
$view['params']['subpage'] = '/page/mp/member/checkin';
$view['params']['angular-modules'] = "'ngRoute','ui.bootstrap'";
$view['params']['js'] = array(array('/mp/member','checkin'));
