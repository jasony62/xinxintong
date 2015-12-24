<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "alter table xxt_mpaccount add qrcode text after name";
$sqls[] = "alter table xxt_mpaccount add public_id varchar(20) not null default '' after qrcode";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo "end update " . __FILE__ . PHP_EOL;