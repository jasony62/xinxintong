<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "ALTER TABLE xxt_site drop shift2pc_page_id";
$sqls[] = "ALTER TABLE xxt_site drop shift2pc_page_name";
$sqls[] = "ALTER TABLE xxt_site drop asparent";
$sqls[] = "ALTER TABLE xxt_site drop site_id";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;