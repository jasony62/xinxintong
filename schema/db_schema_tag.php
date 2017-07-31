<?php
require_once '../db.php';
/*
 * tags
 */
$sql = 'create table if not exists xxt_tag(';
$sql .= 'id int not null auto_increment';
$sql .= ",siteid varchar(32) not null default ''";
$sql .= ',mpid varchar(32) not null';
$sql .= ",creater varchar(40) not null default ''";
$sql .= ",creater_name varchar(255) not null default ''";
$sql .= ",create_at int not null default 0";
$sql .= ',title varchar(255) not null';
$sql .= ',sum int not null default 0';
$sql .= ',seq int not null default 1';
$sql .= ',primary key(id)';
$sql .= ',UNIQUE KEY `tag` (mpid,title)) ENGINE=MyISAM DEFAULT CHARSET=utf8';
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/*
 * relation of tag and article.
 */
$sql = 'create table if not exists xxt_article_tag(';
$sql .= "siteid varchar(32) not null default ''";
$sql .= ',mpid varchar(32) not null';
$sql .= ',res_id int not null';
$sql .= ',tag_id int not null';
$sql .= ",sub_type int not null default 0";
$sql .= ',primary key(mpid,res_id,tag_id)) ENGINE=MyISAM DEFAULT CHARSET=utf8';
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/*
 * 素材分类标签
 */
$sql = 'create table if not exists xxt_matter_tag(';
$sql .= 'id int not null auto_increment';
$sql .= ",siteid varchar(32) not null default ''";
$sql .= ',title varchar(255) not null';
$sql .= ",matter_type varchar(20)";
$sql .= ",sub_type int not null default 0";
$sql .= ',primary key(id)';
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
echo 'finish tag.' . PHP_EOL;