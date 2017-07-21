<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "alter table xxt_enroll drop success_matter_type";
$sqls[] = "alter table xxt_enroll drop success_matter_id";
$sqls[] = "alter table xxt_enroll drop failure_matter_type";
$sqls[] = "alter table xxt_enroll drop failure_matter_id";
$sqls[] = "alter table xxt_enroll drop receiver_page";
$sqls[] = "alter table xxt_enroll drop form_code_id";
$sqls[] = "alter table xxt_enroll drop remark_notice_page";
$sqls[] = "alter table xxt_enroll drop lottery_page_id";
$sqls[] = "alter table xxt_enroll drop can_like_record";
$sqls[] = "alter table xxt_enroll drop can_remark_record";
$sqls[] = "alter table xxt_enroll drop can_signin";
$sqls[] = "alter table xxt_enroll drop can_lottery";
$sqls[] = "alter table xxt_enroll add can_repos char(1) not null default 'N' after notify_submit";
$sqls[] = "alter table xxt_enroll add can_rank char(1) not null default 'N' after can_repos";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;