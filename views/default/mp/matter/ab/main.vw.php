<?php
include_once dirname(dirname(dirname(__FILE__))).'/inmp.vw.php';

TPL::assign('mpid', $_SESSION['mpid']);

$view['params']['layout-body'] = '/mp/matter/ab/frame';
$view['params']['menu'] = '/page/mp/matter';
$view['params']['global_js'] = array('matters-xxt','jquery.form.min');
$view['params']['angular-modules'] = "'matters.xxt','ui.bootstrap'";
$view['params']['css'] = array(array('/mp/matter/ab','main'));
$view['params']['js'] = array(array('/mp/matter/ab','main','deptSelector'));
$view['params']['tips'] = '<p>可以通过【导入】操作批量添加联系人。导入文件必须为【utf-8】编码的【cvs】格式文件。</p><p>文件包含多列，列与列之间用【，】分割。首行为标题列，内容可以为【name,email,tel,dept】的组合。每个联系人可以有多个【tel】列，每一列为一个电话号码；每个联系人可以有多个【dept】列，代表了联系人所属部门，和部门间的层级关系，越靠前的部门层级越高。若一个联系人属于多个部门，那么每个部门都需要一条单独的记录。</p>';
