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

echo "finish xxt_task." . PHP_EOL;