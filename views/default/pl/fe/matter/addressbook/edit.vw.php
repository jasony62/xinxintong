<?php
include_once  'inmp.vw.php';

$view['params']['menu'] = '/rest/mp/app';
$view['params']['layout-body'] = '/pl/fe/matter/addressbook/frame';
$view['params']['global_js'] = array('xxt.ui', 'jquery.form.min');
$view['params']['angular-modules'] = "'ui.xxt','ui.bootstrap'";
$view['params']['css'] = array(array('/mp/app/addressbook', 'edit'));
$view['params']['js'] = array(array('/mp/app/addressbook', 'edit', 'deptSelector'));
