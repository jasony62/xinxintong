<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "alter table xxt_enroll add enroll_app_id varchar(40) not null default '' after tags";
$sqls[] = "alter table xxt_enroll_record add matched_enroll_key varchar(32) not null default ''";
$sqls[] = "alter table xxt_enroll add group_app_id varchar(40) not null default '' after enroll_app_id";
$sqls[] = "alter table xxt_enroll_record add group_enroll_key varchar(32) not null default ''";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;