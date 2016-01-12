<?php
require_once "../db.php";
/*
 * logs
 */
$sql = "create table if not exists xxt_coin_log(";
$sql .= "id int not null auto_increment";
$sql .= ",mpid varchar(32) not null";
$sql .= ",act varchar(255) not null";
$sql .= ",occur_at int not null";
$sql .= ",from varchar(255) not null";
$sql .= ",to varchar(255) not null"; //fid
$sql .= ",detal int not null";
$sql .= ",total int not null";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header("HTTP/1.0 500 Internal Server Error");
	echo "database error: " . $mysqli->error;
}
/*
 * rules
 */
$sql = "create table if not exists xxt_coin_rule(";
$sql .= "mpid varchar(32) not null";
$sql .= ",act varchar(255) not null";
$sql .= ",detal int not null";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header("HTTP/1.0 500 Internal Server Error");
	echo "database error: " . $mysqli->error;
}
echo "finish tag." . PHP_EOL;