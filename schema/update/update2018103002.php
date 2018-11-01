<?php
require_once '../../db.php';

$sqls = [];
//
$sqls[] = "DROP TABLE xxt_enroll_receiver";
$sqls[] = "DROP TABLE xxt_signin_receiver";
$sqls[] = "DROP TABLE xxt_mission_receiver";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;