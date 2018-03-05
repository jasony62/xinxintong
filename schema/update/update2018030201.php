<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "ALTER TABLE xxt_article drop access_control";
$sqls[] = "ALTER TABLE xxt_article drop authapis";
//
$sqls[] = "ALTER TABLE xxt_link drop access_control";
$sqls[] = "ALTER TABLE xxt_link drop authapis";
$sqls[] = "ALTER TABLE xxt_link drop fans_only";
//
$sqls[] = "ALTER TABLE xxt_lottery drop access_control";
$sqls[] = "ALTER TABLE xxt_lottery drop authapis";
//
$sqls[] = "ALTER TABLE xxt_news drop access_control";
$sqls[] = "ALTER TABLE xxt_news drop authapis";
//
$sqls[] = "ALTER TABLE xxt_channel drop access_control";
$sqls[] = "ALTER TABLE xxt_channel drop authapis";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;