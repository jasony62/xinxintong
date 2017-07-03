<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "alter table xxt_enroll_record drop mpid";
$sqls[] = "alter table xxt_enroll_record drop openid";
$sqls[] = "alter table xxt_enroll_record drop vid";
$sqls[] = "alter table xxt_enroll_record drop mid";
$sqls[] = "alter table xxt_enroll_record drop signin_at";
$sqls[] = "alter table xxt_enroll_record drop signin_num";
$sqls[] = "alter table xxt_enroll_record_remark add rid varchar(13) not null default '' after aid";
$sqls[] = "update xxt_enroll_record_remark rr,xxt_enroll_record r set rr.rid=r.rid where rr.enroll_key=r.enroll_key";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;