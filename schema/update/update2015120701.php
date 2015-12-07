<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "alter table xxt_enroll_record_score add nickname varchar(255) not null default '' after openid";
$sqls[] = "alter table xxt_enroll_record_remark add nickname varchar(255) not null default '' after openid";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo "end update " . __FILE__ . PHP_EOL;