<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "alter table xxt_enroll_user add rid varchar(13) not null default '' after aid";
$sqls[] = "alter table xxt_enroll_user add enroll_round_num int not null default 0 after enroll_num";
$sqls[] = "alter table xxt_enroll_user add remark_round_num int not null default 0 after remark_num";
$sqls[] = "alter table xxt_enroll_user add like_round_num int not null default 0 after like_num";
$sqls[] = "alter table xxt_enroll_user add like_remark_round_num int not null default 0 after like_remark_num";
$sqls[] = "alter table xxt_enroll_user add remark_other_round_num int not null default 0 after remark_other_num";
$sqls[] = "alter table xxt_enroll_user add like_other_round_num int not null default 0 after like_other_num";
$sqls[] = "alter table xxt_enroll_user add like_other_remark_round_num int not null default 0 after like_other_remark_num";
$sqls[] = "alter table xxt_enroll_user add user_total_round_coin int not null default 0 after user_total_coin";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;