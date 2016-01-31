<?php
require_once '../db.php';
/**
 * 运营任务，素材和应用的集合
 */
$sql = "create table if not exists xxt_mission (";
$sql .= "id int not null auto_increment";
$sql .= ",mpid varchar(32) not null";
$sql .= ",title varchar(70) not null";
$sql .= ",summary varchar(240) not null";
$sql .= ",creater varchar(40) not null default ''";
$sql .= ",creater_name varchar(255) not null default ''";
$sql .= ",creater_src char(1)";
$sql .= ",create_at int not null";
$sql .= ",modifier varchar(40) not null default ''";
$sql .= ",modifier_name varchar(255) not null default ''";
$sql .= ",modifier_src char(1)";
$sql .= ',modify_at int not null';
$sql .= ",primary key(code)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_contribute): ' . $mysqli->error;
}
/**
 * 组成任务的素材
 */
$sql = "create table if not exists xxt_mission_matter(";
$sql .= "mission_id int not null";
$sql .= ",creater varchar(40) not null";
$sql .= ",creater_name varchar(255) not null default ''";
$sql .= ",creater_src char(1)";
$sql .= ",create_at int not null";
$sql .= ",matter_id varchar(40) not null";
$sql .= ",matter_type varchar(20)";
$sql .= ",seq int not null default 0";
$sql .= ",primary key(mission_id,matter_id,matter_type)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}

echo 'finish xxt_task.' . PHP_EOL;