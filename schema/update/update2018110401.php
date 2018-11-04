<?php
require_once '../../db.php';

$sqls = [];
//
$sql = "create table if not exists xxt_mission_trace(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",mission_id int not null";
$sql .= ",page varchar(13) not null default ''"; //
$sql .= ",userid varchar(40) not null";
$sql .= ",nickname varchar(255) not null default ''";
$sql .= ",event_first varchar(255) not null default ''";
$sql .= ",event_first_at int not null default 0";
$sql .= ",event_end varchar(255) not null default ''";
$sql .= ",event_end_at int not null default 0";
$sql .= ",event_elapse int not null default 0"; // 事件总时长
$sql .= ",events text null"; // 事件
$sql .= ",user_agent text null";
$sql .= ",client_ip varchar(40) not null default ''";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;