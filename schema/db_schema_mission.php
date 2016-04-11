<?php
require_once '../db.php';
/**
 * 运营任务，素材和应用的集合
 */
$sql = "create table if not exists xxt_mission (";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",mpid varchar(32) not null";
$sql .= ",title varchar(70) not null";
$sql .= ",summary varchar(240) not null";
$sql .= ",pic text";
$sql .= ",creater varchar(40) not null default ''";
$sql .= ",creater_name varchar(255) not null default ''";
$sql .= ",creater_src char(1)";
$sql .= ",create_at int not null";
$sql .= ",modifier varchar(40) not null default ''";
$sql .= ",modifier_name varchar(255) not null default ''";
$sql .= ",modifier_src char(1)";
$sql .= ',modify_at int not null';
$sql .= ",access_control char(1) not null default 'N'";
$sql .= ",authapis text";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_mission): ' . $mysqli->error;
}
/**
 * 组成任务的素材
 */
$sql = "create table if not exists xxt_mission_matter(";
$sql .= "mpid varchar(32) not null";
$sql .= ",siteid varchar(32) not null";
$sql .= ",mission_id int not null";
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
	echo 'database error(xxt_mission_matter): ' . $mysqli->error;
}

echo 'finish xxt_mission.' . PHP_EOL;