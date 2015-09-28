<?php
$view['template'] = '/app';
$view['params']['app_title'] = TPL::val('title');
$view['params']['app_view'] = '/app/merchant/pay';
$view['params']['css'] = array(array('/app/merchant', 'pay'));
$view['params']['js'] = array(array('/app/merchant', 'pay'));