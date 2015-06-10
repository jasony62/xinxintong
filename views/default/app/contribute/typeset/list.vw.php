<?php
$view['template'] = '/app';
$view['params']['app_title'] = '我的文章';
$view['params']['app_view'] = '/app/contribute/typeset/list';
$view['params']['global_js'] = array('bootstrap.min','ui-tms');
$view['params']['global_css'] = array('tms');
$view['params']['css'] = array(array('/app/contribute/typeset', 'list'));
$view['params']['js'] = array(array('/app/contribute/typeset', 'list'));

