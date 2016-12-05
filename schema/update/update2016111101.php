<?php
require_once '../../db.php';

$sqls = [];
//
$sql = "create table if not exists xxt_mission_user(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",mission_id int not null";
$sql .= ",userid varchar(40) not null default ''";
$sql .= ",nickname varchar(255) not null default ''";
$sql .= ",first_act_at int not null default 0"; // 首次操作时间
$sql .= ",last_act_at int not null default 0"; // 最后一次操作时间
$sql .= ",enroll_act text"; // 登记应用活动记录
$sql .= ",signin_act text"; // 签到应用活动记录
$sql .= ",group_act text"; // 分组应用活动记录
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