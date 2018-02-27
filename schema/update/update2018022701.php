<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "ALTER TABLE xxt_article drop mission_phase_id";
$sqls[] = "ALTER TABLE xxt_link drop mission_phase_id";
$sqls[] = "ALTER TABLE xxt_channel drop mission_phase_id";
$sqls[] = "ALTER TABLE xxt_enroll drop mission_phase_id";
$sqls[] = "ALTER TABLE xxt_signin drop mission_phase_id";
$sqls[] = "ALTER TABLE xxt_group drop mission_phase_id";
$sqls[] = "ALTER TABLE xxt_plan drop mission_phase_id";
$sqls[] = "ALTER TABLE xxt_wall drop mission_phase_id";
$sqls[] = "ALTER TABLE xxt_mission_matter drop phase_id";
$sqls[] = "ALTER TABLE xxt_mission drop multi_phase";
$sqls[] = "DROP TABLE xxt_mission_phase";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;