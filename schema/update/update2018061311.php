<?php
require_once '../../db.php';

set_time_limit(0);

$sqls = [];
//
$sqls[] = "update xxt_enroll_record_data d,xxt_enroll_record r set d.nickname = r.nickname where d.enroll_key = r.enroll_key and d.nickname = ''";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;