<?php
require_once '../../db.php';

$sqls = array();
$sql = "create table if not exists xxt_log_user_matter(";
$sql .= 'id int not null auto_increment';
$sql .= ',mpid varchar(32) not null';
$sql .= ',openid varchar(255) not null';
$sql .= ',nickname varchar(255) not null';
$sql .= ',matter_id varchar(40) not null';
$sql .= ',matter_type varchar(20) not null';
$sql .= ',matter_title varchar(70) not null';
$sql .= ',last_action_at int not null';
$sql .= ',read_num int not null default 0';
$sql .= ',share_friend_num int not null default 0';
$sql .= ',share_timeline_num int not null default 0';
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo "end update " . __FILE__ . PHP_EOL;