<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "ALTER TABLE xxt_enroll drop can_invite";
$sqls[] = "ALTER TABLE xxt_enroll drop can_discuss";
$sqls[] = "ALTER TABLE xxt_enroll_record drop follower_num";
//
$sqls[] = "ALTER TABLE xxt_enroll_record add agreed char(1) not null default ''";
$sqls[] = "ALTER TABLE xxt_enroll_record add agreed_log text null";
$sqls[] = "ALTER TABLE xxt_enroll_record add like_log longtext";
$sqls[] = "ALTER TABLE xxt_enroll_record add like_num int not null default 0";
//
$sqls[] = "ALTER TABLE xxt_enroll add repos_unit char(1) not null default 'D' after can_repos";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;