<?php
include_once dirname(__FILE__).'/common.vw.php';

TPL::assign('mpid', $_SESSION['mpid']);
$view['params']['global_js'] = array('jquery.form.min');
$view['params']['angular-modules'] = "'ui.bootstrap'";
$view['params']['css'] = array(array('/mp/matter','addressbook'));
$view['params']['js'] = array(array('/mp/matter','addressbook'));
$view['params']['msg_type'] = 'addressbook';
