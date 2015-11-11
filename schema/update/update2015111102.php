<?php
require_once '../../db.php';

$sqls = array();
$sql = 'create table if not exists xxt_enroll_signin_log(';
$sql .= 'id int not null auto_increment';
$sql .= ',mpid varchar(32) not null';
$sql .= ',aid varchar(40) not null';
$sql .= ",enroll_key varchar(32) not null";
$sql .= ",openid varchar(255) not null default ''";
$sql .= ",signin_at int not null default 0"; // 签到时间
$sql .= ',primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8';
$sqls[] = $sql;
$sqls[] = "alter table xxt_enroll_record add signin_num int not null default 0 after signin_at";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo "end update " . __FILE__ . PHP_EOL;