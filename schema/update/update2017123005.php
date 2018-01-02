<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "DROP TABLE xxt_discuss_log";
$sqls[] = "DROP TABLE xxt_discuss_post";
$sqls[] = "DROP TABLE xxt_discuss_thread";
$sqls[] = "DROP TABLE xxt_discuss_thread_user";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;