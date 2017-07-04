<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "alter table xxt_mission drop mpid";
$sqls[] = "alter table xxt_mission drop access_control";
$sqls[] = "alter table xxt_mission drop authapis";
$sqls[] = "alter table xxt_mission_matter drop mpid";
$sqls[] = "alter table xxt_mission_matter add scenario varchar(255) not null default '' after matter_title";
$sqls[] = "alter table xxt_mission_matter add start_at int not null default 0 after scenario";
$sqls[] = "alter table xxt_mission_matter add end_at int not null default 0 after start_at";
$sqls[] = "drop table xxt_enroll_record_score";
$sqls[] = "update xxt_mission_matter m,xxt_enroll e set m.scenario=e.scenario,m.start_at=e.start_at,m.end_at=e.end_at where m.matter_type='enroll' and m.matter_id=e.id";
//
$sqls[] = "alter table xxt_signin add start_at int not null default 0 after pic";
$sqls[] = "alter table xxt_signin add end_at int not null default 0 after start_at";
//
$sqls[] = "alter table xxt_group add start_at int not null default 0 after pic";
$sqls[] = "alter table xxt_group add end_at int not null default 0 after start_at";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;