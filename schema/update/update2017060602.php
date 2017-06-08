<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "alter table xxt_site_member add modify_at int not null after create_at";
$sqls[] = "update xxt_site_member set modify_at=create_at";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;