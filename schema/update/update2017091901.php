<?php
require_once '../../db.php';

$sqls = array();
// addressbook
$sqls[] = "drop table xxt_addressbook";
$sqls[] = "drop table xxt_ab_dept";
$sqls[] = "drop table xxt_ab_person";
$sqls[] = "drop table xxt_ab_person_dept";
$sqls[] = "drop table xxt_ab_tag";
//
$sqls[] = "drop table xxt_checkin";
$sqls[] = "drop table xxt_checkin_log";
//
$sqls[] = "drop table xxt_mpaccount";
$sqls[] = "drop table xxt_mpsetting";
$sqls[] = "drop table xxt_mpadministrator";
$sqls[] = "drop table xxt_mppermission";
$sqls[] = "drop table xxt_mprelay";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;