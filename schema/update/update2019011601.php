<?php
require_once '../../db.php';

$sqls = [];
//
$sqls[] = "ALTER TABLE xxt_enroll_record_data add record_id int not null after group_id";
//
$sqls[] = "update xxt_enroll_record_data rd,xxt_enroll_record r set rd.record_id=r.id where rd.enroll_key=r.enroll_key";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;