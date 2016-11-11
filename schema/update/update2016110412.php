<?php
require_once '../../db.php';

/**
 * 微信企业号粉丝
 */
$sql = "create table if not exists xxt_log_sync(";//企业号同步表
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",sync_at int not null default 0 "; // 与公众号最后一次同步时间
$sql .= ",type varchar(20) not null default ''";//同步类型
$sql .= ",userid varchar(40) not null default ''";
$sql .= ",creater varchar(50) not null default ''";//同步者
$sql .= ",sync_type varchar(50) not null default ''";//同步属性
$sql .= ",sync_table varchar(50) not null default ''";//同步对应的表
$sql .= ",sync_id int not null default 0 ";//插入同步表的id
$sql .= ",sync_data text ";//同步数据
$sql .= ",primary key(id)";
$sql .= ")ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_log_sync): ' . $mysqli->error;
}

echo "end update " . __FILE__ . PHP_EOL;