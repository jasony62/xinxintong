<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "alter table xxt_enroll_record_remark add remark_id int not null default 0 after schema_id";
//
$sqls[] = "alter table xxt_enroll drop mpid";
$sqls[] = "alter table xxt_enroll_page drop mpid";
$sqls[] = "alter table xxt_enroll_page drop check_entry_rule";
$sqls[] = "alter table xxt_enroll_round drop mpid";
$sqls[] = "alter table xxt_enroll_receiver drop mpid";
$sqls[] = "alter table xxt_enroll_receiver drop identity";
$sqls[] = "alter table xxt_enroll_receiver drop idsrc";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;