<?php
require_once '../../db.php';

$sqls = [];
//
$sqls[] = "ALTER TABLE xxt_enroll_user add entry_num int not null default 0 after nickname";
$sqls[] = "ALTER TABLE xxt_enroll_user add last_entry_at int not null default 0 after entry_num";
$sqls[] = "ALTER TABLE xxt_enroll_user add total_elapse int not null default 0 after last_entry_at";
//
$sqls[] = "ALTER TABLE xxt_mission_user add entry_num int not null default 0 after nickname";
$sqls[] = "ALTER TABLE xxt_mission_user add last_entry_at int not null default 0 after entry_num";
$sqls[] = "ALTER TABLE xxt_mission_user add total_elapse int not null default 0 after last_entry_at";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;