<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "alter table xxt_platform add autoup_templatepage char(1) not null default 'Y' after template_page_name";
//
$sqls[] = "alter table xxt_platform add autoup_sitepage char(1) not null default 'Y' after site_page_name";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;