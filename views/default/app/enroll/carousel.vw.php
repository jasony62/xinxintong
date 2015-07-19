<?php
$act = TPL::val('enroll');
$view['template'] = '/app';
$view['params']['app_title'] = $act->title;
$view['params']['app_view'] = '/app/enroll/carousel';
$view['params']['global_css'] = array('idangerous.swiper');
$view['params']['global_js'] = array('idangerous.swiper.min');
$view['params']['css'] = array(array('/app/enroll','carousel'));
$view['params']['js'] = array(array('/app/enroll','carousel'));
