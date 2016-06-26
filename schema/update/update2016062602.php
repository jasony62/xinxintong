<?php
require_once '../../db.php';
$sqls = array();
//
$sqls[] = "alter table xxt_enroll_record_stat add siteid varchar(32) not null first";
$sqls[] = "alter table xxt_enroll_record add first_enroll_at int not null after enroll_at";
$sqls[] = "update xxt_enroll_record set first_enroll_at=enroll_at";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;