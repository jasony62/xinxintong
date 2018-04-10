<?php
require_once '../../db.php';

$sqls = [];
//
$sqls[] = "ALTER TABLE xxt_enroll_log change target_id target_id varchar(40) not null";
$sqls[] = "ALTER TABLE xxt_enroll_notice change event_target_id event_target_id varchar(40) not null";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;