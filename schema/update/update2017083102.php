<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "alter table xxt_timer_push rename xxt_timer_task";
$sqls[] = "ALTER TABLE xxt_timer_task drop mpid";
$sqls[] = "ALTER TABLE xxt_timer_task add pattern char(1) not null default '' after matter_id";
$sqls[] = "ALTER TABLE xxt_timer_task add left_count int not null default 1 after wday";
$sqls[] = "ALTER TABLE xxt_timer_task add task_model varchar(20) not null default ''";
$sqls[] = "ALTER TABLE xxt_timer_task add task_arguments text";
$sqls[] = "ALTER TABLE xxt_timer_task add task_expire_at int not null default 0";
$sqls[] = "ALTER TABLE xxt_log_timer drop mpid";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;