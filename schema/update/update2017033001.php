<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "alter table xxt_platform add home_nav text";
$sqls[] = "alter table xxt_platform add is_show_site char(1) not null default 'Y'";
$sqls[] = "alter table xxt_platform add is_show_template char(1) not null default 'Y'";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;