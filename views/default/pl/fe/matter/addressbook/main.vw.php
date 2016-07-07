<?php
include_once dirname(__FILE__).'/inapp.vw.php';

$view['params']['app-view'] = '/pl/fe/matter/addressbook';
$view['params']['angular-modules'] = "'ui.bootstrap'";
$view['params']['css'] = array(array('/pl/fe/matter/addressbook','main'));
$view['params']['js'] = array(array('/pl/fe/matter/addressbook','main'));