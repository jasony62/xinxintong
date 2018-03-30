<?php
require_once '../../db.php';

$sqls = [];
//
$sqls[] = "ALTER TABLE xxt_enroll_log add enroll_key varchar(32) not null after rid";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;