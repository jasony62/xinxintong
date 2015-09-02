<?php
require_once '../../db.php';

$sqls = array();
$sql = "create table if not exists xxt_merchant_staff(";
$sql .= 'id int not null auto_increment';
$sql .= ',mpid varchar(32) not null';
$sql .= ',shopid varchar(40) not null'; // contribute's id
$sql .= ',role char(1) not null'; // Initiator|Reviewer|Typesetter
$sql .= ',identity varchar(100) not null';
$sql .= ",idsrc char(2) not null default ''";
$sql .= ",label varchar(255) not null default ''";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo "end update " . __FILE__ . PHP_EOL;
