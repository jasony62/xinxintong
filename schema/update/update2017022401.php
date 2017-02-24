<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "alter table xxt_short_url add target_title varchar(70) not null default '' after siteid";
$sqls[] = "alter table xxt_short_url add can_favor char(1) not null default 'N'";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;