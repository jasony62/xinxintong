<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "alter table xxt_mission add user_app_type varchar(10) not null default ''";
$sqls[] = "update xxt_mission set user_app_type='enroll' where user_app_id<>''";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;