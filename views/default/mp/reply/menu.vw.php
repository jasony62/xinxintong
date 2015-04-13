<?php
include_once dirname(__FILE__) . '/common.vw.php';

$view['params']['css'][] = array('/mp/reply','menu');
$view['params']['angular-modules'] = "'ui.tms','matters.xxt'";
$view['params']['js'][] = array('/mp/reply','menu');
$view['params']['msg_type'] = 'menu';
$view['params']['tips'] = '<p>一级菜单的个数应为【1~3】个；子菜单个数应为【2~5】个；一级菜单最多4个汉字，二级菜单最多7个汉字。</p><p>直接打开的【外部链接】必须以【http://】开头。</p><p>如果包含了二级菜单，一级菜单就不再响应点击事件。</p><p>可以通过鼠标拖动调整菜单按钮的位置。</p><p>菜单在【作为链接直接打开】的回复模式下无法进行权限控制，可以通过将链接做成素材的方式实现。</p><p>【白名单】可以采用邮箱域名的形式简化配置，例如：@abc.com。</p><p>菜单项只有保存后，才能指定回复内容。</p>';
