<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "alter table xxt_enroll_record add siteid varchar(32) not null default '' after mpid";
$sqls[] = "alter table xxt_enroll_record add userid varchar(40) not null default '' after rid";
$sqls[] = "alter table xxt_enroll_signin_log add siteid varchar(32) not null default '' after mpid";
$sqls[] = "alter table xxt_enroll_signin_log add userid varchar(40) not null default '' after enroll_key";
$sqls[] = "alter table xxt_enroll_signin_log add nickname varchar(255) not null default '' after userid";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo "end update " . __FILE__ . PHP_EOL;