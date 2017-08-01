<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "alter table xxt_enroll_record add group_id varchar(32) not null default '' after rid";
$sqls[] = "alter table xxt_enroll_record_data add group_id varchar(32) not null default '' after rid";
$sqls[] = "alter table xxt_enroll_record_remark add enroll_group_id varchar(32) not null default '' after rid";
$sqls[] = "alter table xxt_enroll_record_remark add group_id varchar(32) not null default '' after enroll_userid";
$sqls[] = "alter table xxt_enroll_user add group_id varchar(32) not null default '' after rid";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;