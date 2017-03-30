<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "alter table xxt_log_tmplmsg_batch add event_name varchar(255) not null default '' after siteid";
$sqls[] = "alter table xxt_log_tmplmsg_detail add close_at int not null default 0";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;