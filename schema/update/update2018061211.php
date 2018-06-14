<?php
require_once '../../db.php';

$sqls = [];
//
$sqls[] = "update xxt_enroll_record_data d set d.nickname = (select r.nickname from xxt_enroll_record r where r.enroll_key = d.enroll_key and r.aid = d.aid) where d.nickname = ''";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;