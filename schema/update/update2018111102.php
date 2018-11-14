<?php
require_once '../../db.php';

$sqls = [];
//
$sqls[] = "ALTER TABLE  xxt_enroll drop op_short_url_code";
$sqls[] = "ALTER TABLE  xxt_enroll drop rp_short_url_code";
//
$sqls[] = "ALTER TABLE  xxt_signin drop op_short_url_code";
//
$sqls[] = "ALTER TABLE  xxt_plan drop op_short_url_code";
$sqls[] = "ALTER TABLE  xxt_plan drop rp_short_url_code";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;