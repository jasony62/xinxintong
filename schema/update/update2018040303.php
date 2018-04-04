<?php
require_once '../../db.php';

//
$sqls[] = "ALTER TABLE xxt_mission add wxacode_url text null";
$sqls[] = "ALTER TABLE xxt_enroll add wxacode_url text null";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;