<?php
require_once '../db.php';
/*
 * tags
 */
$sql = 'create table if not exists xxt_tag(';
$sql .= 'id int not null auto_increment';
$sql .= ",siteid varchar(32) not null default ''";
$sql .= ",creater varchar(40) not null default ''";
$sql .= ",creater_name varchar(255) not null default ''";
$sql .= ",create_at int not null default 0";
$sql .= ',title varchar(255) not null';
$sql .= ',sum int not null default 0';
$sql .= ',seq int not null default 1';
$sql .= ",sub_type char(1) not null default 'M'";
$sql .= ',primary key(id)';
$sql .= ',UNIQUE KEY `tag` (siteid,title,sub_type)) ENGINE=MyISAM DEFAULT CHARSET=utf8';
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}

echo 'finish tag.' . PHP_EOL;