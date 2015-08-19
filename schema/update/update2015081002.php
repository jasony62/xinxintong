<?php
require_once '../../db.php';

$sqls = array();
$sql = "create table if not exists xxt_timer_push(";
$sql .= 'id int not null auto_increment';
$sql .= ',mpid varchar(32) not null';
$sql .= ",enabled char(1) not null default 'Y'";
$sql .= ',matter_type varchar(20) not null';
$sql .= ",matter_id varchar(40) not null";
$sql .= ",min int not null default -1";
$sql .= ",hour int not null default -1";
$sql .= ",mday int not null default -1";
$sql .= ",mon int not null default -1";
$sql .= ",wday int not null default -1";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;

$sql = "create table if not exists xxt_log_timer(";
$sql .= 'id int not null auto_increment';
$sql .= ',mpid varchar(32) not null';
$sql .= ",task_id int not null";
$sql .= ",occur_at int not null";
$sql .= ",result text";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo "end update " . __FILE__ . PHP_EOL;
