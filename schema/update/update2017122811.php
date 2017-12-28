<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "ALTER TABLE xxt_enroll_record_data add multitext_seq int not null default 0 after schema_id";
$sqls[] = "ALTER TABLE xxt_enroll_record_remark add record_data_id int not null default 0 after schema_id";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;