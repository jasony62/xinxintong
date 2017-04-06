<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "alter table xxt_article add mission_phase_id varchar(13) not null default '' after mission_id";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;