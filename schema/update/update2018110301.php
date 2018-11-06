<?php
require_once '../../db.php';

$sqls = [];
//
$sqls[] = "ALTER TABLE xxt_mission_user add score float not null default 0 after user_total_coin";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;