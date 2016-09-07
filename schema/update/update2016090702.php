<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "alter table xxt_lottery change summary summary varchar(240) not null default ''";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;