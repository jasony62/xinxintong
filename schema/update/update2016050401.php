<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "alter table xxt_lottery_task add task_name varchar(20) not null after title";
$sqls[] = "update xxt_lottery_task set task_name=task_type";
$sqls[] = "update xxt_lottery_task set task_type='can_play'";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo "end update " . __FILE__ . PHP_EOL;