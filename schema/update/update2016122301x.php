<?php
require_once '../../db.php';

$sqls = [];
$sqls[] = "alter table xxt_wall add last_sync_at int not null default 0";
$sqls[] = "alter table xxt_wall add source_app varchar(255) not null default ''";
$sqls[] = "alter table xxt_wall add data_schemas text";

$sqls[] = "alter table xxt_wall_enroll add wx_openid varchar(255) not null default '' after nickname";
$sqls[] = "alter table xxt_wall_enroll add yx_openid varchar(255) not null default '' after wx_openid";
$sqls[] = "alter table xxt_wall_enroll add qy_openid varchar(255) not null default '' after yx_openid";
$sqls[] = "alter table xxt_wall_enroll add msg_num int not null default 0 after last_msg_at";
$sqls[] = "alter table xxt_wall_enroll add enroll_key varchar(32) not null default ''";
$sqls[] = "alter table xxt_wall_enroll add data text";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;