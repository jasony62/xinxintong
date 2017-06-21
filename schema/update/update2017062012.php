<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "alter table xxt_site_home_channel add display_name varchar(70) not null default '' after title";
//
$sqls[] = "update xxt_site_home_channel set display_name = title";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;