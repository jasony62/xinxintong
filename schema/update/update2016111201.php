<?php
require_once '../../db.php';

$sqls = [];
$sqls[] = "alter table xxt_mission add user_app_id varchar(40) not null default ''";
$sqls[] = "alter table xxt_mission_user add assoc_enroll_app text";
$sqls[] = "alter table xxt_mission_user add assoc_group_app text";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;