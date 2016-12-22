<?php
require_once '../../db.php';

$sqls = [];
$sqls[] = "alter table xxt_enroll_record add wx_openid varchar(255) not null default '' after nickname ";
$sqls[] = "alter table xxt_enroll_record add yx_openid varchar(255) not null default '' after wx_openid ";
$sqls[] = "alter table xxt_enroll_record add qy_openid varchar(255) not null default '' after yx_openid ";
$sqls[] = "alter table xxt_enroll_record add headimgurl varchar(255) not null default '' after qy_openid ";

$sqls[] = "alter table xxt_group_player add wx_openid varchar(255) not null default '' after nickname ";
$sqls[] = "alter table xxt_group_player add yx_openid varchar(255) not null default '' after wx_openid ";
$sqls[] = "alter table xxt_group_player add qy_openid varchar(255) not null default '' after yx_openid ";
$sqls[] = "alter table xxt_group_player add headimgurl varchar(255) not null default '' after qy_openid ";

//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;