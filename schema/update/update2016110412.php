<?php
require_once '../../db.php';

/**
 * 微信企业号粉丝
 */
$sql = "create table if not exists xxt_log_sync(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",sync_at int not null"; // 与公众号最后一次同步时间
$sql .= ",type varchar(20) not null default ''";
$sql .= ",userid varchar(40) not null default ''";
$sql .= ",creater varchar(255) not null default ''";
$sql .= ",sync_type varchar(20) not null default ''";
$sql .= ",sync_table varchar(20) not null default ''";
$sql .= ",sync_id varchar(20) not null default ''";
$sql .= ",sync_data text not null default ''";
$sql .= ",primary key(id)";
$sql .= ")ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_log_sync): ' . $mysqli->error;
}

echo "end update " . __FILE__ . PHP_EOL;