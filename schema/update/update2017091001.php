<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "update xxt_enroll set start_at=create_at where start_at=0";
$sqls[] = "update xxt_signin set start_at=create_at where start_at=0";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;