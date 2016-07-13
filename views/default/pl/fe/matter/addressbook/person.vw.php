<?php
include_once dirname(dirname(dirname(__FILE__))).'/inmp.vw.php';

$view['params']['layout-body'] = '/mp/app/addressbook/person';
$view['params']['menu'] = '/rest/mp/app';
$view['params']['css'] = array(array('/mp/app/addressbook','person'));
$view['params']['js'] = array(array('/mp/app/addressbook','person','deptSelector'));
$view['params']['angular-modules'] = "'ui.bootstrap'";
