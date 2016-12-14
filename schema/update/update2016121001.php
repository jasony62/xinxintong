<?php
require_once '../../db.php';

$sqls = [];
$sqls[] = "alter table xxt_log_user_matter add operation varchar(255) not null";
$sqls[] = "alter table xxt_log_user_matter add operate_at int not null";
$sqls[] = "alter table xxt_log_user_matter add operate_data text";
$sqls[] = "alter table xxt_log_user_matter add matter_last_op char(1) not null default 'Y'";
$sqls[] = "alter table xxt_log_user_matter add matter_op_num int not null default 1";
$sqls[] = "alter table xxt_log_user_matter add user_last_op char(1) not null default 'Y'";
$sqls[] = "alter table xxt_log_user_matter add user_op_num int not null default 1";
$sqls[] = "alter table xxt_log_user_matter add user_agent text";
$sqls[] = "alter table xxt_log_user_matter add client_ip varchar(40) not null default ''";
$sqls[] = "alter table xxt_log_user_matter add referer text";
//
$sqls[] = "alter table xxt_coin_log add matter_id varchar(40) not null after siteid";
$sqls[] = "alter table xxt_coin_log add matter_type varchar(20) not null after matter_id";
$sqls[] = "alter table xxt_coin_log add matter_title varchar(70) not null after matter_type";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;