<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "alter table xxt_log_matter_op change mpid site_id varchar(32) not null";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo "end update " . __FILE__ . PHP_EOL;