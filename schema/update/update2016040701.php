<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "alter table xxt_call_text add siteid varchar(32) not null after id";
$sqls[] = "alter table xxt_call_menu add siteid varchar(32) not null after mpid";
$sqls[] = "alter table xxt_call_qrcode add siteid varchar(32) not null after id";
$sqls[] = "alter table xxt_call_other add siteid varchar(32) not null after id";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo "end update " . __FILE__ . PHP_EOL;