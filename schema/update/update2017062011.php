<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "alter table xxt_site add autoup_homepage char(1) not null default 'N' after home_page_name";
//
$sqls[] = "alter table xxt_platform add autoup_homepage char(1) not null default 'N' after home_page_name";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;