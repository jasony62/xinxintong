<?php
require_once '../../db.php';

$sqls = array();
//
//$sqls[] = "alter table xxt_home_matter add weight int not null default 0";
$sqls[] = "alter table xxt_home_matter add site_name varchar(50) not null after siteid";
$sqls[] = "update xxt_home_matter h,xxt_site s set h.site_name=s.name where h.siteid=s.id";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;