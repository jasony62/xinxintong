<?php
require_once '../../db.php';
$sqls = array();
//
$sqls[] = "alter table xxt_enroll_receiver add join_at int not null default 0 after aid";
$sqls[] = "alter table xxt_enroll_receiver add userid varchar(40) not null default ''";
$sqls[] = "alter table xxt_enroll_receiver add nickname varchar(255) not null default ''";
$sqls[] = "alter table xxt_enroll_receiver add sns_user text";
//
$sqls[] = "alter table xxt_call_qrcode_yx add create_at int not null after scene_id";
$sqls[] = "alter table xxt_call_qrcode_wx add create_at int not null after scene_id";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;