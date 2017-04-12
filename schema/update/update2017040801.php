<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = 'alter table xxt_enroll_record add submit_log text';
//
$sqls[] = 'alter table xxt_enroll_record_data drop primary key';
$sqls[] = 'alter table xxt_enroll_record_data add id int not null auto_increment first,add primary key(id)';
$sqls[] = "alter table xxt_enroll_record_data add submit_at int not null default 0 after enroll_key";
$sqls[] = "alter table xxt_enroll_record_data add userid varchar(40) not null default '' after submit_at";
$sqls[] = 'alter table xxt_enroll_record_data change name schema_id varchar(40) not null';
$sqls[] = "alter table xxt_enroll_record_data add remark_num int not null default 0";
$sqls[] = "alter table xxt_enroll_record_data add last_remark_at int not null default 0";
$sqls[] = "alter table xxt_enroll_record_data add modify_log longtext";
//
$sqls[] = "alter table xxt_enroll_record_remark add userid varchar(40) not null default '' after enroll_key";
$sqls[] = "alter table xxt_enroll_record_remark add user_src char(1) not null default 'S' after userid";
$sqls[] = "alter table xxt_enroll_record_remark add schema_id varchar(40) not null default ''";
$sqls[] = "alter table xxt_enroll_record_remark drop openid";
$sqls[] = "alter table xxt_enroll_record_remark change remark content text";
//
$sqls[] = "alter table xxt_enroll_record_score change openid userid varchar(40) not null default ''";
$sqls[] = "alter table xxt_enroll_record_score add schema_id varchar(40) not null default ''";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;