<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "alter table xxt_fans add coin int not null";
$sql = "create table if not exists xxt_coin_log(";
$sql .= "id int not null auto_increment";
$sql .= ",mpid varchar(32) not null";
$sql .= ",act varchar(255) not null";
$sql .= ",occur_at int not null";
$sql .= ",payer varchar(255) not null";
$sql .= ",payee varchar(255) not null";
$sql .= ",detal int not null";
$sql .= ",total int not null";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;
$sql = "create table if not exists xxt_coin_rule(";
$sql .= "id int not null auto_increment";
$sql .= ",mpid varchar(32) not null";
$sql .= ",act varchar(255) not null";
$sql .= ",detal int not null";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo "end update " . __FILE__ . PHP_EOL;