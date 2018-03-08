<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "ALTER TABLE xxt_enroll add rank_config text null after rp_config";
$sqls[] = "ALTER TABLE xxt_enroll drop access_control";
$sqls[] = "ALTER TABLE xxt_enroll drop authapis";
$sqls[] = "ALTER TABLE xxt_enroll drop user_task";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;