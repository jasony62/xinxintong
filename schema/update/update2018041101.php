<?php
require_once '../../db.php';

$sqls = [];
//
$sqls[] = "ALTER TABLE xxt_enroll_log change target_id target_id int not null";
$sqls[] = "ALTER TABLE xxt_enroll_notice change event_target_id event_target_id int not null";
$sqls[] = "update xxt_enroll_notice n,xxt_enroll_record r set n.event_target_id=r.id where n.event_target_type='record' and n.event_target_id=r.enroll_key";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;