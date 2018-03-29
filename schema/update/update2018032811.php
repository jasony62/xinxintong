<?php
require_once '../../db.php';

$sqls = [];
//
$sqls[] = "ALTER TABLE xxt_group add auto_sync char(1) not null default 'N' after source_app";
$sqls[] = "ALTER TABLE xxt_group add sync_round varchar(32) not null default '' after auto_sync";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;