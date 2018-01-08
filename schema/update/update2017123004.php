<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "DROP TABLE xxt_call_acl";
$sqls[] = "DROP TABLE xxt_call_menu";
$sqls[] = "DROP TABLE xxt_call_other";
$sqls[] = "DROP TABLE xxt_call_qrcode";
$sqls[] = "DROP TABLE xxt_call_text";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;