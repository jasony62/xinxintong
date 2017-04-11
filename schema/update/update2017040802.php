<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = 'update xxt_enroll_record_data ra,xxt_enroll_record r set ra.userid=r.userid where ra.enroll_key=r.enroll_key';
$sqls[] = "delete from xxt_enroll_record_data where value=''";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;