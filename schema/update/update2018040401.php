<?php
require_once '../../db.php';
//
$sqls[] = "ALTER TABLE xxt_enroll_record_remark add modify_at int not null default 0 after create_at";
$sqls[] = "ALTER TABLE xxt_enroll_record_remark add modify_log longtext null";
$sqls[] = "update xxt_enroll_record_remark set modify_at=create_at where modify_at=0";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;