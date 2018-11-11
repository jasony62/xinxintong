<?php
require_once '../../db.php';

$sqls = [];
//
$sqls[] = "ALTER TABLE  xxt_enroll_round add purpose char(1) not null default 'C' after state";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;