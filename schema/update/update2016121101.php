<?php
require_once '../../db.php';

$sqls = [];
$sqls[] = "alter table xxt_article add can_discuss char(1) not null default 'N' after can_fullsearch";
$sqls[] = "alter table xxt_article change body body longtext";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;