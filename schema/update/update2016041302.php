<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "alter table xxt_enroll add scenario varchar(255) not null default '' after summary";
$sqls[] = "alter table xxt_enroll add scenario_config text after scenario";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo "end update " . __FILE__ . PHP_EOL;