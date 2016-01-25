<?php
$view['template'] = '/app';
$view['params']['app_title'] = '审核版面';
$view['params']['app_view'] = '/app/contribute/review/news-r';
$view['params']['global_js'] = array('angular-route.min', 'angular-sanitize.min', 'bootstrap.min', 'ui-bootstrap.min', 'ui-bootstrap-tpls.min', 'ui-tms', 'matters-xxt');
$view['params']['global_css'] = array('tms');
$view['params']['css'] = array(array('/app/contribute/review', 'news-r'));
$view['params']['js'] = array(array('/app/contribute', 'base'), array('/app/contribute/review', 'news-r'));