<?php
require_once '../../db.php';

$sqls = [];
//
$sqls[] = "ALTER TABLE account_group add view_name varchar(10) not null default 'default'";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;