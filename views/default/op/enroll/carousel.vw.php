<?php
$act = TPL::val('enroll');
$view['template'] = '/app';
$view['params']['app_title'] = $act->title;
$view['params']['app_view'] = '/op/enroll/carousel';
$view['params']['global_css'] = array('swiper.min');
$view['params']['global_js'] = array('swiper.min');
$view['params']['css'] = array(array('/op/enroll','carousel'));
$view['params']['js'] = array(array('/op/enroll','carousel'));
