<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "update xxt_enroll_record_remark r,xxt_enroll_record_data d set r.data_id = d.id where r.enroll_key = d.enroll_key and r.schema_id = d.schema_id and d.multitext_seq = 0";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;