<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "alter table xxt_site_wx add by_platform char(1) not null default 'N' after create_at";
$sqls[] = "alter table xxt_site_yx add by_platform char(1) not null default 'N' after create_at";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo "end update " . __FILE__ . PHP_EOL;