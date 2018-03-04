<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "ALTER TABLE xxt_enroll_user add last_recommend_at int not null default 0 after like_other_remark_num";
$sqls[] = "ALTER TABLE xxt_enroll_user add recommend_num int not null default 0 after last_recommend_at";
$sqls[] = "ALTER TABLE xxt_enroll_user add modify_log text null";
$sqls[] = "ALTER TABLE xxt_mission_user add last_recommend_at int not null default 0 after like_other_remark_num";
$sqls[] = "ALTER TABLE xxt_mission_user add recommend_num int not null default 0 after last_recommend_at";
$sqls[] = "ALTER TABLE xxt_mission_user add modify_log text null";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;