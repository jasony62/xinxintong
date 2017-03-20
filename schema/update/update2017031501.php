<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "alter table xxt_log_timer change mpid mpid varchar(32) not null default ''";
$sqls[] = "alter table xxt_log_timer add siteid varchar(32) not null default '' after mpid";
//
$sqls[] = "alter table xxt_timer_push change mpid mpid varchar(32) not null default ''";
$sqls[] = "alter table xxt_timer_push add siteid varchar(32) not null default '' after mpid";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;