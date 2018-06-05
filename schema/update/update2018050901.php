<?php
require_once '../../db.php';

$sqls = [];
//
$sql = 'create table if not exists xxt_enroll_tag(';
$sql .= 'id bigint not null auto_increment';
$sql .= ",siteid varchar(32) not null";
$sql .= ",aid varchar(40) not null";
$sql .= ',label varchar(255) not null';
$sql .= ",assign_num int not null default 0";
$sql .= ",user_num int not null default 0";
$sql .= ",public char(1) not null default 'N'";
$sql .= ",forbidden char(1) not null default 'N'";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;
//
$sql = 'create table if not exists xxt_enroll_user_tag(';
$sql .= 'id bigint not null auto_increment';
$sql .= ",siteid varchar(32) not null";
$sql .= ",aid varchar(40) not null";
$sql .= ',tag_id bigint not null';
$sql .= ",userid varchar(40) not null";
$sql .= ',create_at int not null';
$sql .= ",state tinyint not null default 1"; // 事件是否有效
$sql .= ",assign_num int not null default 0";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;
//
$sql = 'create table if not exists xxt_enroll_tag_assign(';
$sql .= 'id bigint not null auto_increment';
$sql .= ",siteid varchar(32) not null";
$sql .= ",aid varchar(40) not null";
$sql .= ',tag_id bigint not null';
$sql .= ',user_tag_id bigint not null';
$sql .= ",userid varchar(40) not null";
$sql .= ',assign_at int not null';
$sql .= ',target_id int not null'; // 被打标签的填写记录
$sql .= ',target_type tinyint not null default 1'; // 1:record
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;
//
$sql = 'create table if not exists xxt_enroll_tag_target(';
$sql .= 'id bigint not null auto_increment';
$sql .= ",siteid varchar(32) not null";
$sql .= ",aid varchar(40) not null";
$sql .= ',tag_id bigint not null';
$sql .= ',first_assign_at int not null';
$sql .= ',last_assign_at int not null';
$sql .= ',target_id int not null'; // 被打标签的对象
$sql .= ',target_type tinyint not null default 1'; // 1:record
$sql .= ',assign_num int not null';
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