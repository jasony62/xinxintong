<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = 'alter table account_group add p_platform_manage tinyint not null default 0';
$sqls[] = "insert into account_group(group_id,group_name,asdefault,p_mpgroup_create,p_mp_create,p_mp_permission,p_platform_manage) values(9,'平台运营',0,1,1,1,1)";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;