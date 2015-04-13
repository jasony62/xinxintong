<?php
$view['template'] = '/app';
$view['params']['app_title'] = TPL::val('title');
$view['params']['app_view'] = '/activity/discuss/members';
$view['params']['css'] = array(array('/activity/discuss','members'));
$view['params']['js'] = array(array('/activity/discuss/','members'));
