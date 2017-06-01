<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "alter table xxt_site_member_schema add is_wx_fan char(1) not null default 'N'";
$sqls[] = "alter table xxt_site_member_schema add is_yx_fan char(1) not null default 'N'";
$sqls[] = "alter table xxt_site_member_schema add is_qy_fan char(1) not null default 'N'";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;