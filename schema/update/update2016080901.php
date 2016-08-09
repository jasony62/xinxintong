<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "alter table xxt_signin add tags text";
$sqls[] = "alter table xxt_signin_record add data text after referrer";
$sqls[] = "alter table xxt_group_player add data text";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;