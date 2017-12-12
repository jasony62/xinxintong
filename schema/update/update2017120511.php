<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "update xxt_log_matter_op set operation = 'updateData' where operation = 'update'";
$sqls[] = "update xxt_log_matter_op set operation = 'removeData' where operation = 'remove'";
$sqls[] = "update xxt_log_matter_op set operation = 'restoreData' where operation = 'restore'";
$sqls[] = "update xxt_log_user_matter set operation = 'updateData' where operation = 'update'";
$sqls[] = "update xxt_log_user_matter set operation = 'removeData' where operation = 'remove'";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;