<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "alter table xxt_channel add matter_mg_tag varchar(255) not null default ''";
$sqls[] = "alter table xxt_link add matter_mg_tag varchar(255) not null default ''";
$sqls[] = "alter table xxt_contribute add matter_mg_tag varchar(255) not null default ''";
$sqls[] = "alter table xxt_text add matter_mg_tag varchar(255) not null default ''";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;