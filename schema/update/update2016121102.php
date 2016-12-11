<?php
require_once '../../db.php';

$sqls = [];
$sqls[] = "alter table xxt_log add user_agent text";
$sqls[] = "alter table xxt_log add referer text";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;