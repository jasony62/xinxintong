<?php
$act = TPL::val('activity');
$view['template'] = '/app';
$view['params']['app_title'] = $act->title;
$view['params']['app_view'] = '/activity/enroll/carousel';
$view['params']['global_css'] = array('idangerous.swiper');
$view['params']['global_js'] = array('idangerous.swiper.min');
$view['params']['css'] = array(array('/activity/enroll','carousel'));
$view['params']['js'] = array(array('/activity/enroll','carousel'));
