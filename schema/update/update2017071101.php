<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "update xxt_wall_page w,xxt_code_page c set w.code_name=c.name where w.code_id=c.id";
$sqls[] = "alter table xxt_wall drop mpid";
$sqls[] = "alter table xxt_wall_page drop mpid";
$sqls[] = "alter table xxt_wall_enroll drop mpid";
$sqls[] = "alter table xxt_wall_log drop mpid";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;