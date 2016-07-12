<?php
include_once dirname(dirname(dirname(__FILE__))).'/inmp.vw.php';

$view['params']['layout-body'] = '/pl/fe/matter/addressbook/person';
$view['params']['menu'] = '/rest/mp/app';
$view['params']['css'] = array(array('/pl/fe/matter/addressbook','person'));
$view['params']['js'] = array(array('/pl/fe/matter/addressbook','person','deptSelector'));
$view['params']['angular-modules'] = "'ui.bootstrap'";
