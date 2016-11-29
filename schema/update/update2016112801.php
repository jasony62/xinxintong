<?php
require_once '../../db.php';

$sqls = [];
$sqls[] = "alter table xxt_log_matter_op add user_last_op char(1) not null default 'N' after last_op";
$sqls[] = "update xxt_log_matter_op set user_last_op='Y' where last_op='Y'";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;