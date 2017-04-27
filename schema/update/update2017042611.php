<?php
require_once '../../db.php';

$sqls = array();
/**
 * 团队收藏记录
 */
$sql = "create table if not exists xxt_site_friend_favor(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",creater varchar(40) not null";
$sql .= ",creater_name varchar(255) not null";
$sql .= ",favor_at int not null";
$sql .= ",from_siteid varchar(32) not null";
$sql .= ",matter_id varchar(40) not null";
$sql .= ",matter_type varchar(20) not null";
$sql .= ",matter_title varchar(70) not null";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;