<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "DROP TABLE xxt_visitor";
$sqls[] = "DROP TABLE xxt_member";
$sqls[] = "DROP TABLE xxt_member_card";
$sqls[] = "DROP TABLE xxt_member_department";
$sqls[] = "DROP TABLE xxt_member_tag";
$sqls[] = "DROP TABLE xxt_member_authapi";
$sqls[] = "DROP TABLE xxt_access_token";
//
$sqls[] = "DROP TABLE xxt_fans";
$sqls[] = "DROP TABLE xxt_fansgroup";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;