<?php
$view['template'] = '/app';
$view['params']['app_title'] = '通讯录';
$view['params']['app_view'] = '/app/addressbook/index';
$view['params']['global_js'] = array('ng-infinite-scroll.min','bootstrap.min','ui-bootstrap.min','ui-bootstrap-tpls.min');
$view['params']['global_css'] = array('tms');
$view['params']['css'] = array(array('/app/addressbook','index'));
$view['params']['js'] = array(array('/app/addressbook','index'));
