<?php
require_once '../../db.php';

$sqls = [];
//
$sql = "create table if not exists xxt_enroll_search(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",aid varchar(40) not null";
$sql .= ",keyword varchar(255) not null";
$sql .= ",user_num int not null default 0"; // 使用人数
$sql .= ",used_num int not null default 0"; // 使用总数
$sql .= ",agreed char(1) not null default ''"; // 是否推荐（Y：推荐）
$sql .= ",state tinyint not null default 1"; //0:remove,1:normal
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;
//
$sql = "create table if not exists xxt_enroll_user_search(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",aid varchar(40) not null";
$sql .= ",userid varchar(40) not null default ''";
$sql .= ",nickname varchar(255) not null default ''";
$sql .= ",create_at int not null default 0"; 
$sql .= ",last_use_at int not null default 0"; // 最后使用时间
$sql .= ",search_id int not null default 0"; //
$sql .= ",used_num int not null default 0"; // 使用总数
$sql .= ",state tinyint not null default 1"; //0:remove,1:normal
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;