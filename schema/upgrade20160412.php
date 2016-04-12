<?php
require_once '../db.php';
//
$sqls = array();
$sqls[] = "update xxt_enroll_page set siteid=mpid where mpid<>''";
$sqls[] = "update xxt_enroll_round set siteid=mpid where mpid<>''";
$sqls[] = "update xxt_enroll_receiver set siteid=mpid where mpid<>''";
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo "database error($sql): " . $mysqli->error;
	}
}