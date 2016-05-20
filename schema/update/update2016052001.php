<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "alter table xxt_signin_log add state tinyint not null default 1";
$sqls[] = "alter table xxt_enroll_record add verified char(1) not null default 'N'";
$sqls[] = "alter table xxt_signin_record add verified char(1) not null default 'Y'";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;