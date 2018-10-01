<?php
require_once '../../db.php';

$sqls = [];
//
$sqls[] = "ALTER TABLE xxt_timer_task add offset_matter_type varchar(2) not null default 'N' after notweekend";
$sqls[] = "ALTER TABLE xxt_timer_task add offset_matter_id varchar(13) not null default '' after offset_matter_type";
$sqls[] = "ALTER TABLE xxt_timer_task add offset_mode char(2) not null default '' after offset_matter_id";
$sqls[] = "ALTER TABLE xxt_timer_task add offset_min int not null default 0 after offset_mode";
$sqls[] = "ALTER TABLE xxt_timer_task add offset_hour int not null default 0 after offset_min";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;