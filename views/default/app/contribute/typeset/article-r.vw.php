<?php
$view['template'] = '/app';
$view['params']['app_title'] = '我的文章';
$view['params']['app_view'] = '/app/contribute/typeset/article-r';
$view['params']['global_js'] = array('angular-route.min', 'angular-sanitize.min', 'bootstrap.min', 'ui-bootstrap.min', 'ui-bootstrap-tpls.min', 'ui-tms', 'matters-xxt');
$view['params']['global_css'] = array('tms');
$view['params']['css'] = array(array('/app/contribute/typeset', 'article-r'));
$view['params']['js'] = array(array('/app/contribute', 'base'), array('/app/contribute/typeset', 'article-r'));