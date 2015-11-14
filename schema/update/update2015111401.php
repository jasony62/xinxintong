<?php
require_once '../../db.php';

$sqls = array();
$sql = 'create table if not exists xxt_wall_page(';
$sql .= 'id int not null auto_increment';
$sql .= ',mpid varchar(32) not null';
$sql .= ',wid varchar(32) not null'; //wall
$sql .= ",creater varchar(40) not null default ''";
$sql .= ",create_at int not null";
$sql .= ",type varchar(30) not null"; //op
$sql .= ",name varchar(70) not null default ''";
$sql .= ",title varchar(70) not null default ''";
$sql .= ",summary varchar(240) not null default ''";
$sql .= ',code_id int not null default 0'; // from xxt_code_page
$sql .= ",seq int not null";
$sql .= ',primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8';
$sqls[] = $sql;

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo "end update " . __FILE__ . PHP_EOL;