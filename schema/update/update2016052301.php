<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "drop table xxt_group_result";
$sqls[] = "alter table xxt_enroll add extattrs text";
$sqls[] = "alter table xxt_signin add extattrs text";
$sqls[] = "alter table xxt_signin drop active_round";
$sqls[] = "alter table xxt_group add extattrs text";
$sqls[] = "alter table xxt_group_round add extattrs text";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;