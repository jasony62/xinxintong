<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "alter table xxt_lottery_task add task_type varchar(20) not null after title";
$sqls[] = "alter table xxt_lottery_task add task_params text after task_type";
$sqls[] = "alter table xxt_lottery add read_num int not null default 0";
$sqls[] = "alter table xxt_lottery add share_friend_num int not null default 0";
$sqls[] = "alter table xxt_lottery add share_timeline_num int not null default 0";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo "end update " . __FILE__ . PHP_EOL;