<?php
$view['template'] = '/app';
$view['params']['app_title'] = '我的稿件';
$view['params']['app_view'] = '/app/contribute/initiate/list';
$view['params']['global_js'] = array(array('angular-route.min', 'angular-sanitize.min', 'bootstrap.min', 'ui-bootstrap.min', 'ui-bootstrap-tpls.min', 'resumable'), array('ui-tms', 'matters-xxt', true));
$view['params']['global_css'] = array('tms');
$view['params']['css'] = array(
	array('/app/contribute/initiate', 'list', array("(min-width: 769px)")),
	array('/app/contribute/initiate', 'list-xs', array("(max-width: 768px)")),
);
$view['params']['js'] = array(array('/app/contribute', 'base'), array('/app/contribute/initiate', 'list'));