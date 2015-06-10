<?php
$view['template'] = '/app';
$view['params']['app_title'] = '我的文章';
$view['params']['app_view'] = '/app/contribute/initiate/list';
$view['params']['global_js'] = array('bootstrap.min','ui-bootstrap.min','ui-bootstrap-tpls.min','ui-tms');
$view['params']['global_css'] = array('tms');
$view['params']['css'] = array(array('/app/contribute/initiate', 'list'));
$view['params']['js'] = array(array('/app/contribute/initiate', 'list'));

