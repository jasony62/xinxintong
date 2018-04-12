<?php
require_once '../../db.php';

$sqls = [];
//
$sql = "create table if not exists xxt_enroll_notice(";
$sql .= "id bigint not null auto_increment";
$sql .= ",siteid varchar(32) not null default ''";
$sql .= ",aid varchar(40) not null";
$sql .= ",rid varchar(13) not null default ''";
$sql .= ",enroll_key varchar(32) not null";
$sql .= ",userid varchar(40) not null default ''"; // 被通知的用户
$sql .= ",nickname varchar(255) not null default ''"; // 被通知的用户的昵称
$sql .= ",notice_reason varchar(255) not null default ''"; // 被通知的原因
$sql .= ",event_userid varchar(40) not null default ''"; // 发起事件的用户
$sql .= ",event_nickname varchar(255) not null default ''"; // 发起事件的用户昵称
$sql .= ",event_target_id int not null"; // 事件操作的对象
$sql .= ",event_target_type varchar(20) not null"; // 事件操作的对象的类型
$sql .= ",event_name varchar(255) not null default ''"; // 事件名称
$sql .= ",event_op varchar(10) not null default ''"; // 事件操作
$sql .= ",event_at int not null";
$sql .= ",state tinyint not null default 1";
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