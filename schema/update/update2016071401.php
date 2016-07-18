<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "alter table xxt_signin_round change end_start_code_id after_end_code_id int not null default 0";
$sqls[] = "alter table xxt_signin_round add late_at int not null default 0 after after_end_code_id";
$sqls[] = "alter table xxt_signin_round add after_late_code_id int not null default 0 after late_at";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;