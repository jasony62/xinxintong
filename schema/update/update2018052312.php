<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "ALTER TABLE xxt_enroll_user add repos_read_num int not null default 0 after nickname";
$sqls[] = "ALTER TABLE xxt_enroll_user add topic_read_num int not null default 0 after repos_read_num";
$sqls[] = "ALTER TABLE xxt_enroll_user add cowork_read_num int not null default 0 after topic_read_num";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;