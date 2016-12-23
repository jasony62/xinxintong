<?php
require_once '../../db.php';

$sqls = [];
$sqls[] = "alter table xxt_wall_enroll add matter_type varchar(20) ";
$sqls[] = "alter table xxt_wall_enroll add matter_id varchar(40) ";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;