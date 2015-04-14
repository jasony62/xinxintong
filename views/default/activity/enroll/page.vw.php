<?php
$view['template'] = '/app';
$view['params']['app_title'] = TPL::val('title');
$view['params']['app_view'] = '/activity/enroll/page';
$view['params']['global_js'] = array('ng-infinite-scroll.min');
$view['params']['css'] = array(array('/activity/enroll','page'));
$view['params']['js'] = array(array('/activity/enroll','page'));
