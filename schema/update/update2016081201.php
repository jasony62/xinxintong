<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "alter table xxt_signin_record add signin_log text after signin_num";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;