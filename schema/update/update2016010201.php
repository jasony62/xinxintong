<?php
require_once '../../db.php';

$sqls = array();
$sql = 'create table if not exists xxt_enroll_record_stat(';
$sql .= 'aid varchar(40) not null';
$sql .= ',create_at int not null';
$sql .= ',id varchar(40) not null';
$sql .= ',title varchar(255) not null';
$sql .= ',v varchar(40) not null';
$sql .= ',l varchar(255) not null';
$sql .= ',c int not null';
$sql .= ',primary key(aid,id,v)) ENGINE=MyISAM DEFAULT CHARSET=utf8';
$sqls[] = $sql;

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo "end update " . __FILE__ . PHP_EOL;