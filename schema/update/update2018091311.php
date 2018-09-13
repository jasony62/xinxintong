<?php
require_once '../../db.php';

$sqls = [];
// enroll_record
$sqls[] = "ALTER TABLE xxt_enroll_record add dislike_log longtext null after like_data_num";
$sqls[] = "ALTER TABLE xxt_enroll_record add dislike_num int not null default 0 after dislike_log";
$sqls[] = "ALTER TABLE xxt_enroll_record add dislike_data_num int not null default 0 after dislike_num";

// enroll_record_data
$sqls[] = "ALTER TABLE xxt_enroll_record_data add dislike_log longtext null after like_num";
$sqls[] = "ALTER TABLE xxt_enroll_record_data add dislike_num int not null default 0 after dislike_log";

// xxt_enroll_record_remark
$sqls[] = "ALTER TABLE xxt_enroll_record_remark add dislike_log longtext null after like_num";
$sqls[] = "ALTER TABLE xxt_enroll_record_remark add dislike_num int not null default 0 after dislike_log";

// mission
$sqls[] = "ALTER TABLE xxt_mission_user add last_dislike_at int not null default 0 after like_remark_num";
$sqls[] = "ALTER TABLE xxt_mission_user add dislike_num int not null default 0 after last_dislike_at";
$sqls[] = "ALTER TABLE xxt_mission_user add last_dislike_cowork_at int not null default 0 after dislike_num";
$sqls[] = "ALTER TABLE xxt_mission_user add dislike_cowork_num int not null default 0 after last_dislike_cowork_at";
$sqls[] = "ALTER TABLE xxt_mission_user add last_dislike_remark_at int not null default 0 after dislike_cowork_num";
$sqls[] = "ALTER TABLE xxt_mission_user add dislike_remark_num int not null default 0 after last_dislike_remark_at";
$sqls[] = "ALTER TABLE xxt_mission_user add last_do_dislike_at int not null default 0 after do_like_remark_num";
$sqls[] = "ALTER TABLE xxt_mission_user add do_dislike_num int not null default 0 after last_do_dislike_at";
$sqls[] = "ALTER TABLE xxt_mission_user add last_do_dislike_cowork_at int not null default 0 after do_dislike_num";
$sqls[] = "ALTER TABLE xxt_mission_user add do_dislike_cowork_num int not null default 0 after last_do_dislike_cowork_at";
$sqls[] = "ALTER TABLE xxt_mission_user add last_do_dislike_remark_at int not null default 0 after do_dislike_cowork_num";
$sqls[] = "ALTER TABLE xxt_mission_user add do_dislike_remark_num int not null default 0 after last_do_dislike_remark_at";

// enroll_user
$sqls[] = "ALTER TABLE xxt_enroll_user add last_dislike_at int not null default 0 after do_like_remark_num";
$sqls[] = "ALTER TABLE xxt_enroll_user add dislike_num int not null default 0 after last_dislike_at";
$sqls[] = "ALTER TABLE xxt_enroll_user add last_dislike_cowork_at int not null default 0 after dislike_num";
$sqls[] = "ALTER TABLE xxt_enroll_user add dislike_cowork_num int not null default 0 after last_dislike_cowork_at";
$sqls[] = "ALTER TABLE xxt_enroll_user add last_dislike_remark_at int not null default 0 after dislike_cowork_num";
$sqls[] = "ALTER TABLE xxt_enroll_user add dislike_remark_num int not null default 0 after last_dislike_remark_at";
$sqls[] = "ALTER TABLE xxt_enroll_user add last_do_dislike_at int not null default 0 after dislike_remark_num";
$sqls[] = "ALTER TABLE xxt_enroll_user add do_dislike_num int not null default 0 after last_do_dislike_at";
$sqls[] = "ALTER TABLE xxt_enroll_user add last_do_dislike_cowork_at int not null default 0 after do_dislike_num";
$sqls[] = "ALTER TABLE xxt_enroll_user add do_dislike_cowork_num int not null default 0 after last_do_dislike_cowork_at";
$sqls[] = "ALTER TABLE xxt_enroll_user add last_do_dislike_remark_at int not null default 0 after do_dislike_cowork_num";
$sqls[] = "ALTER TABLE xxt_enroll_user add do_dislike_remark_num int not null default 0 after last_do_dislike_remark_at";


foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;