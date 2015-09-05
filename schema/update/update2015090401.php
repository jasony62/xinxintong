<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "ALTER TABLE xxt_lottery_award add takeaway_at int not null default 0 after takeaway";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo "end update " . __FILE__ . PHP_EOL;
