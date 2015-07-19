<?php
$view['template'] = '/app';
$view['params']['app_title'] = TPL::val('title');
$view['params']['app_view'] = '/app/wall/main';
$view['params']['css'] = array(array('/app/wall','main'));
$view['params']['js'] = array(array('/app/wall','main'));
