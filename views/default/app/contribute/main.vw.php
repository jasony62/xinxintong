<?php
$view['template'] = '/app';
$view['params']['app_title'] = '投稿';
$view['params']['app_view'] = '/app/contribute/main';
$view['params']['global_js'] = array('angular-sanitize.min', 'ui-tms');
$view['params']['global_css'] = array('tms');
$view['params']['css'] = array(array('/app/contribute', 'main'));
$view['params']['js'] = array(array('/app/contribute', 'main'));