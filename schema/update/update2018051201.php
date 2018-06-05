<?php
require_once '../../db.php';
//
$sqls[] = "ALTER TABLE xxt_enroll drop repos_unit";
$sqls[] = "ALTER TABLE xxt_enroll_record drop data_tag";
$sqls[] = "DROP TABLE xxt_enroll_record_tag";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;