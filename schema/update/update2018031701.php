<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "ALTER TABLE xxt_enroll_user change last_recommend_at last_agree_at int not null default 0";
$sqls[] = "ALTER TABLE xxt_enroll_user change recommend_num agree_num int not null default 0";
$sqls[] = "ALTER TABLE xxt_enroll_user change last_remark_other_at last_do_remark_at int not null default 0";
$sqls[] = "ALTER TABLE xxt_enroll_user change remark_other_num do_remark_num int not null default 0";
$sqls[] = "ALTER TABLE xxt_enroll_user change last_like_other_at last_do_like_at int not null default 0";
$sqls[] = "ALTER TABLE xxt_enroll_user change like_other_num do_like_num int not null default 0";
$sqls[] = "ALTER TABLE xxt_enroll_user change last_like_other_remark_at last_do_like_remark_at int not null default 0";
$sqls[] = "ALTER TABLE xxt_enroll_user change like_other_remark_num do_like_remark_num int not null default 0";
//
$sqls[] = "ALTER TABLE xxt_enroll_user add last_cowork_at int not null default 0 after enroll_num";
$sqls[] = "ALTER TABLE xxt_enroll_user add cowork_num int not null default 0 after last_cowork_at";
$sqls[] = "ALTER TABLE xxt_enroll_user add last_do_cowork_at int not null default 0 after cowork_num";
$sqls[] = "ALTER TABLE xxt_enroll_user add do_cowork_num int not null default 0 after last_do_cowork_at";
$sqls[] = "ALTER TABLE xxt_enroll_user add last_remark_cowork_at int not null default 0 after remark_num";
$sqls[] = "ALTER TABLE xxt_enroll_user add remark_cowork_num int not null default 0 after last_remark_cowork_at";
$sqls[] = "ALTER TABLE xxt_enroll_user add last_like_cowork_at int not null default 0 after like_num";
$sqls[] = "ALTER TABLE xxt_enroll_user add like_cowork_num int not null default 0 after last_like_cowork_at";
$sqls[] = "ALTER TABLE xxt_enroll_user add last_agree_cowork_at int not null default 0 after agree_num";
$sqls[] = "ALTER TABLE xxt_enroll_user add agree_cowork_num int not null default 0 after last_agree_cowork_at";
$sqls[] = "ALTER TABLE xxt_enroll_user add last_agree_remark_at int not null default 0 after agree_cowork_num";
$sqls[] = "ALTER TABLE xxt_enroll_user add agree_remark_num int not null default 0 after last_agree_remark_at";
$sqls[] = "ALTER TABLE xxt_enroll_user add last_do_like_cowork_at int not null default 0 after do_like_num";
$sqls[] = "ALTER TABLE xxt_enroll_user add do_like_cowork_num int not null default 0 after last_do_like_cowork_at";
//
$sqls[] = "ALTER TABLE xxt_mission_user change last_recommend_at last_agree_at int not null default 0";
$sqls[] = "ALTER TABLE xxt_mission_user change recommend_num agree_num int not null default 0";
$sqls[] = "ALTER TABLE xxt_mission_user change last_like_other_at last_do_like_at int not null default 0";
$sqls[] = "ALTER TABLE xxt_mission_user change like_other_num do_like_num int not null default 0";
$sqls[] = "ALTER TABLE xxt_mission_user change last_like_other_remark_at last_do_like_remark_at int not null default 0";
$sqls[] = "ALTER TABLE xxt_mission_user change like_other_remark_num do_like_remark_num int not null default 0";
$sqls[] = "ALTER TABLE xxt_mission_user change last_remark_other_at last_do_remark_at int not null default 0";
$sqls[] = "ALTER TABLE xxt_mission_user change remark_other_num do_remark_num int not null default 0";
//
$sqls[] = "ALTER TABLE xxt_mission_user add last_cowork_at int not null default 0 after enroll_num";
$sqls[] = "ALTER TABLE xxt_mission_user add cowork_num int not null default 0 after last_cowork_at";
$sqls[] = "ALTER TABLE xxt_mission_user add last_do_cowork_at int not null default 0 after cowork_num";
$sqls[] = "ALTER TABLE xxt_mission_user add do_cowork_num int not null default 0 after last_do_cowork_at";
$sqls[] = "ALTER TABLE xxt_mission_user add last_remark_cowork_at int not null default 0 after remark_num";
$sqls[] = "ALTER TABLE xxt_mission_user add remark_cowork_num int not null default 0 after last_remark_cowork_at";
$sqls[] = "ALTER TABLE xxt_mission_user add last_like_cowork_at int not null default 0 after like_num";
$sqls[] = "ALTER TABLE xxt_mission_user add like_cowork_num int not null default 0 after last_like_cowork_at";
$sqls[] = "ALTER TABLE xxt_mission_user add last_agree_cowork_at int not null default 0 after agree_num";
$sqls[] = "ALTER TABLE xxt_mission_user add agree_cowork_num int not null default 0 after last_agree_cowork_at";
$sqls[] = "ALTER TABLE xxt_mission_user add last_agree_remark_at int not null default 0 after agree_cowork_num";
$sqls[] = "ALTER TABLE xxt_mission_user add agree_remark_num int not null default 0 after last_agree_remark_at";
$sqls[] = "ALTER TABLE xxt_mission_user add last_do_like_cowork_at int not null default 0 after do_like_num";
$sqls[] = "ALTER TABLE xxt_mission_user add do_like_cowork_num int not null default 0 after last_do_like_cowork_at";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;