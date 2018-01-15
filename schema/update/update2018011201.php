<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "ALTER TABLE xxt_enroll_record_remark add state tinyint not null default 1";
$sqls[] = "ALTER TABLE xxt_enroll_user add state tinyint not null default 1";
$sqls[] = "ALTER TABLE xxt_enroll_record_tag add state tinyint not null default 1";
$sqls[] = "ALTER TABLE xxt_mission_user add state tinyint not null default 1";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;