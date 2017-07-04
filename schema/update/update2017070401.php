<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "alter table xxt_mission drop mpid";
$sqls[] = "alter table xxt_mission_matter drop mpid";
$sqls[] = "alter table xxt_mission_matter add start_at int not null default 0 after matter_title";
$sqls[] = "alter table xxt_mission_matter add end_at int not null default 0 after start_at";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;