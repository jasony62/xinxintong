<?php
require_once '../../db.php';

$sqls = array();
//
$sql = "create table if not exists xxt_site_contribute(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null"; // 接收投稿的团队
$sql .= ",from_siteid varchar(32) not null"; // 进行投稿的团队
$sql .= ",creater varchar(40) not null"; // 投稿用户
$sql .= ",creater_name varchar(255) not null"; // 投稿用户
$sql .= ",matter_id varchar(40) not null";
$sql .= ",matter_type varchar(20) not null";
$sql .= ",matter_title varchar(70) not null";
$sql .= ",matter_summary varchar(240) not null default ''";
$sql .= ",matter_pic text";
$sql .= ",create_at int not null"; // 投稿时间
$sql .= ",browse_at int not null default 0"; // 浏览时间
$sql .= ",close_at int not null default 0"; // 关闭时间
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
//
$sqls[] = $sql;

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;