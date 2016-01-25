<?php
$view['template'] = '/app';
$view['params']['app_title'] = '稿件浏览';
$view['params']['app_view'] = '/app/contribute/initiate/article-r';
$view['params']['global_js'] = array('angular-route.min', 'angular-sanitize.min', 'bootstrap.min', 'ui-bootstrap.min', 'ui-bootstrap-tpls.min', 'tinymce/tinymce.min', 'ui-tms', 'matters-xxt');
$view['params']['global_css'] = array('tms');
$view['params']['css'] = array(
	array('/app/contribute/initiate', 'article-r', array("(min-width: 769px)")),
	array('/app/contribute/initiate', 'article-r-xs', array("(max-width: 768px)")),
);
$view['params']['js'] = array(array('/app/contribute', 'base'), array('/app/contribute/initiate', 'article-r'));