<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "alter table xxt_site_member_schema add at_user_home char(1) not null default 'N'";
//
$sqls[] = "update xxt_site_member_schema set at_user_home='Y'";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;