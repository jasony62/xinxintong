<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "alter table xxt_enroll add can_cowork char(1) not null default 'N' after can_siteuser";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;