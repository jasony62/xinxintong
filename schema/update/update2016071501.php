<?php
require_once '../../db.php';

$sqls = array();
//
// task
$sql = "create table if not exists xxt_task_token (";
$sql .= "siteid varchar(32) not null";
$sql .= ",code char(4) not null";
$sql .= ",name varchar(255) not null"; // 任务的名称
$sql .= ",creater varchar(40) not null default ''";
$sql .= ",creater_name varchar(255) not null default ''"; //from account or fans
$sql .= ",create_at int not null";
$sql .= ",expire_at int not null";
$sql .= ",params text";
$sql .= ",primary key(code)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;

// task log
$sql = "create table if not exists xxt_task_token_log (";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",code char(4) not null";
$sql .= ",name varchar(255) not null"; // 任务的名称
$sql .= ",creater varchar(40) not null default ''";
$sql .= ",creater_name varchar(255) not null default ''"; //from account or fans
$sql .= ",create_at int not null";
$sql .= ",expire_at int not null";
$sql .= ",params text";
$sql .= ",disposer varchar(40) not null default ''";
$sql .= ",disposer_name varchar(255) not null default ''"; //from account or fans
$sql .= ",dispose_at int not null";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;