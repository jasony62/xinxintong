<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "alter table xxt_enroll_record_data add rid varchar(13) not null default '' after aid";
$sqls[] = "update xxt_enroll_record_data d,xxt_enroll_record r set d.rid=r.rid where d.enroll_key=r.enroll_key";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;