<?php
require_once '../../db.php';

/**
 * 微信企业号粉丝
 */
$sql = "create table if not exists xxt_site_qyfan(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",openid varchar(255) not null";
// $sql .= ",groupid int default 0"; // 缺省属于未分组
$sql .= ",subscribe_at int not null";
$sql .= ",unsubscribe_at int not null default 0";
$sql .= ",sync_at int not null"; // 与公众号最后一次同步时间
$sql .= ",headimgurl varchar(255) not null default ''";
$sql .= ",nickname varchar(255) not null default ''";
$sql .= ",mobile varchar(20) not null default ''";
$sql .= ",email varchar(50) not null default ''";
$sql .= ",weixinid varchar(50) not null default ''";
$sql .= ",extattr text"; //扩展属性
$sql .= ",depts text";//和用户部门列表的关联
$sql .= ",tags text";//和标签列表的关联字段
$sql .= ",sex tinyint not null default 0";
$sql .= ",city varchar(255) not null default ''";
$sql .= ",province varchar(255) not null default ''";
$sql .= ",country varchar(255) not null default ''";
$sql .= ",forbidden char(1) not null default 'N'";
$sql .= ",userid varchar(40) not null default ''"; // 对应的站点用户帐号（should remove）
$sql .= ",primary key(id)";
$sql .= ")ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_site_qyfan): ' . $mysqli->error;
}
$sql = "ALTER TABLE `xxt_site_qyfan` ADD UNIQUE fanpk( `siteid`, `openid`)";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_site_qyfan): ' . $mysqli->error;
}

echo "end update " . __FILE__ . PHP_EOL;