<?php
require_once '../../db.php';

$sqls = [];
//
$sqls[] = "ALTER TABLE xxt_enroll drop can_siteuser";
$sqls[] = "ALTER TABLE xxt_enroll drop can_repos";
$sqls[] = "ALTER TABLE xxt_enroll drop can_rank";
$sqls[] = "ALTER TABLE xxt_enroll drop can_cowork";

//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;