<?php
require_once '../../db.php';

$sqls = [];
//
$sqls[] = "delete from xxt_enroll_page where type = 'L'";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;