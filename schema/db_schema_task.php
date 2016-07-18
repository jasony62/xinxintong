<?php
require_once "../db.php";
/**
 * 手机端申请移动端完成的任务定义
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
echo "finish task." . PHP_EOL;