<?php
require_once '../../db.php';
//
$sqls[] = "ALTER TABLE xxt_platform add home_qrcode_group text null after home_carousel";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;