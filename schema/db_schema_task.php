<?php
require_once "../db.php";
/**
 * 短链接任务
 */
$sql = "create table if not exists xxt_task (";
$sql .= "code char(4) not null";
$sql .= ",siteid varchar(32) not null";
$sql .= ",userid varchar(40) not null";
$sql .= ",url text not null";
$sql .= ",create_at int not null";
$sql .= ",primary key(code)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header("HTTP/1.0 500 Internal Server Error");
	echo "database error(xxt_task): " . $mysqli->error;
}
/**
 * 令牌模式，只保存和任务相关的数据
 */
// task
$sql = "create table if not exists xxt_task_token (";
$sql .= "siteid varchar(32) not null";
$sql .= ",code char(4) not null";
$sql .= ",name varchar(255) not null"; // 任务的名称
$sql .= ",creater varchar(40) not null default ''";
$sql .= ",creater_name varchar(255) not null default ''"; //from account or fans
$sql .= ",create_at int not null";
$sql .= ",expire_at int not null";
$sql .= ",params text";
$sql .= ",primary key(code)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header("HTTP/1.0 500 Internal Server Error");
	echo "database error(xxt_task_token): " . $mysqli->error;
}
// task log
$sql = "create table if not exists xxt_task_token_log (";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",code char(4) not null";
$sql .= ",name varchar(255) not null"; // 任务的名称
$sql .= ",creater varchar(40) not null default ''";
$sql .= ",creater_name varchar(255) not null default ''"; //from account or fans
$sql .= ",create_at int not null";
$sql .= ",expire_at int not null";
$sql .= ",params text";
$sql .= ",disposer varchar(40) not null default ''";
$sql .= ",disposer_name varchar(255) not null default ''"; //from account or fans
$sql .= ",dispose_at int not null";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header("HTTP/1.0 500 Internal Server Error");
	echo "database error(xxt_task_token_log): " . $mysqli->error;
}
/**
 * 短链接
 */
$sql = "create table if not exists xxt_short_url (";
$sql .= "id int not null auto_increment";
$sql .= ",code char(4) not null";
$sql .= ",state int not null default 1";
$sql .= ",siteid varchar(32) not null";
$sql .= ",target_title varchar(70) not null default ''";
$sql .= ",target_url text not null";
$sql .= ",creater varchar(40) not null";
$sql .= ",creater_name varchar(255) not null default ''";
$sql .= ",create_at int not null";
$sql .= ",expire_at int not null default 0";
$sql .= ",password varchar(40) not null default ''";
$sql .= ",count_limit int not null default 0"; // 可访问的次数
$sql .= ",count_left int not null default 1"; // 剩余访问的次数
$sql .= ",can_favor char(1) not null default 'N'"; // 是否支持被收藏
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header("HTTP/1.0 500 Internal Server Error");
	echo "database error(xxt_task): " . $mysqli->error;
}
//
$sql = "create table if not exists xxt_short_url_token (";
$sql .= "id int not null auto_increment";
$sql .= ",code varchar(40) not null";
$sql .= ",state int not null default 1";
$sql .= ",access_token varchar(255) not null";
$sql .= ",create_at int not null";
$sql .= ",expire_at int not null";
$sql .= ",user_agent text";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header("HTTP/1.0 500 Internal Server Error");
	echo "database error(xxt_task): " . $mysqli->error;
}

echo "finish task." . PHP_EOL;