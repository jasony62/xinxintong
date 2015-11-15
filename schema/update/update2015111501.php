<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = 'alter table xxt_lottery_log add award_title varchar(20) not null after aid';
$sqls[] = 'update xxt_lottery_log l,xxt_lottery_award a set l.award_title=a.title where l.aid=a.aid';
$sqls[] = "alter table xxt_lottery_log add enroll_key varchar(32) not null default ''";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo "end update " . __FILE__ . PHP_EOL;