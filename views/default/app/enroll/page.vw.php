<?php
$view['template'] = '/app';
$view['params']['app_title'] = TPL::val('title');
$view['params']['app_view'] = '/app/enroll/page';
$view['params']['global_js'] = array('angular-sanitize.min','ng-infinite-scroll.min','xxt.share','xxt.image','xxt.geo');
$view['params']['css'] = array(array('/app/enroll','page'));
$view['params']['js'] = array(array('/app/enroll','page'));
