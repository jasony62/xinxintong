<?php
require_once '../../db.php';

$sqls = array();
//
$sql = "create table if not exists xxt_site_active(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",userid varchar(40) not null";
$sql .= ",nickname varchar(255) not null";
$sql .= ",user_active_sum int not null default 0";//用户产生的活跃数总数
$sql .= ",operation varchar(255) not null";
$sql .= ",operation_active_sum int not null default 0";//行为产生的活跃数总数
$sql .= ",year int not null";
$sql .= ",year_active_sum int not null default 0"; 
$sql .= ",month int not null";
$sql .= ",month_active_sum int not null default 0"; 
$sql .= ",day int not null";
$sql .= ",day_active_sum int not null default 0"; 
$sql .= ",operation_at int not null";
$sql .= ",active_last_op char(1) not null default 'Y'";
$sql .= ",user_last_op char(1) not null default 'Y'";
$sql .= ",operation_last_op char(1) not null default 'Y'";
$sql .= ",active_one_num int not null default 0"; //单次增加的活跃数
$sql .= ",active_sum int not null default 0"; //活跃数总数
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;