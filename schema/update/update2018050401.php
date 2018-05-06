<?php
require_once '../../db.php';

$sqls = [];
//
$sql = "create table if not exists xxt_enroll_record_favor(";
$sql .= "id bigint not null auto_increment";
$sql .= ",aid varchar(40) not null";
$sql .= ",siteid varchar(32) not null default ''";
$sql .= ",record_id int not null"; // 填写记录的ID
$sql .= ",favor_unionid varchar(40) not null"; // 用户的注册账号ID
$sql .= ",favor_at int not null"; // 收藏填写的时间
$sql .= ",state tinyint not null default 1";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;
//
$sqls[] = "ALTER TABLE xxt_enroll_record add favor_num int not null default 0";
//
$sql = "create table if not exists xxt_enroll_topic(";
$sql .= "id int not null auto_increment";
$sql .= ",aid varchar(40) not null";
$sql .= ",siteid varchar(32) not null default ''";
$sql .= ",unionid varchar(40) not null default ''";
$sql .= ",nickname varchar(255) not null default ''";
$sql .= ",create_at int not null";
$sql .= ",title varchar(255) not null default ''";
$sql .= ",summary varchar(240) not null default ''";
$sql .= ",state tinyint not null default 1";
$sql .= ",rec_num int not null default 0"; // 包含的记录数
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;
//
$sql = "create table if not exists xxt_enroll_topic_record(";
$sql .= "id bigint not null auto_increment";
$sql .= ",aid varchar(40) not null";
$sql .= ",siteid varchar(32) not null default ''";
$sql .= ",topic_id int not null";
$sql .= ",record_id int not null";
$sql .= ",assign_at int not null"; // 指定时间
$sql .= ",seq int not null default 0";
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