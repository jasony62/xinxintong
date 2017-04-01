<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "ALTER TABLE  `xxt_log_mpsend` ADD  `siteid` VARCHAR( 32 ) NOT NULL AFTER  `id`";
$sqls[] = "update `xxt_log_mpsend` set siteid=mpid";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;