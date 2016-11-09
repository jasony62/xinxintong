<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "alter table xxt_mission_acl add creater_name varchar(255) not null default '' after creater";
$sqls[] = "update xxt_mission_acl a,xxt_mission m set a.creater_name=m.creater_name where a.mission_id=m.id";
$sqls[] = "alter table xxt_site add summary varchar(240) not null default '' after name";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;