<?php
include_once dirname(__FILE__) . '/common.vw.php';

$view['params']['css'] = array(array('/mp/matter', 'channels', true));
$view['params']['js'] = array(array('/mp/matter', 'channels', true));
$view['params']['msg_type'] = 'channels';
$view['params']['tips'] = '<p>【频道】会根据指定的【显示数量】和图文或链接加入频道的时间，自动将最新的图文或链接组合成多图文。</p><p>如果设置了【固定标题】，该标题将替换图文列表中第一个图文的标题。</p>';