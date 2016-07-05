<?php
require_once '../../db.php';
$sqls = array();
//
$sqls[] = "alter table xxt_site_member change sync_at sync_at int not null default 0";
$sqls[] = "alter table xxt_site_member change name name varchar(255) not null default ''";
$sqls[] = "alter table xxt_site_member change mobile mobile varchar(20) not null default ''";
$sqls[] = "alter table xxt_site_member change email email varchar(50) not null default ''";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;