<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "ALTER TABLE `xxt_site_account` DROP PRIMARY KEY, ADD PRIMARY KEY(`siteid`,`uid`);";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;