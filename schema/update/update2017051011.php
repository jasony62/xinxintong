<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "alter table xxt_site add home_qrcode_group text after home_carousel";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;