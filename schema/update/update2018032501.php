<?php
require_once '../../db.php';

$sqls = [];
//
$sql = "create table if not exists xxt_enroll_log(";
$sql .= "id bigint not null auto_increment";
$sql .= ",aid varchar(40) not null";
$sql .= ",siteid varchar(32) not null default ''";
$sql .= ",rid varchar(13) not null default ''";
$sql .= ",group_id varchar(32) not null default ''";
$sql .= ",userid varchar(40) not null default ''"; // 发起操作的用户
$sql .= ",nickname varchar(255) not null default ''"; // 发起操作的用户昵称
$sql .= ",event_name varchar(255) not null default ''"; // 事件名称
$sql .= ",event_op varchar(10) not null default ''"; // 事件操作
$sql .= ",event_at int not null";
$sql .= ",target_id int not null"; // 事件操作的对象
$sql .= ",target_type varchar(20) not null"; // 事件操作的对象的类型
$sql .= ",earn_coin int not null default 0"; // 获得的积分奖励
$sql .= ",owner_userid varchar(40) not null default ''"; // 受到操作影响的用户
$sql .= ",owner_nickname varchar(255) not null default ''"; // 受到操作影响的用户昵称
$sql .= ",owner_earn_coin int not null default 0"; // 获得的积分奖励
$sql .= ",undo_event_id int not null default 0"; // 产生的结果是否已经被其他事件撤销
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