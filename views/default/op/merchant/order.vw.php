<?php
$view['template'] = '/app';
$view['params']['app_title'] = TPL::val('title');
$view['params']['app_view'] = '/op/merchant/order';
$view['params']['css'] = array(array('/op/merchant', 'order'));
$view['params']['js'] = array(array('/op/merchant', 'order'));
