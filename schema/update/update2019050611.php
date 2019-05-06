<?php
require_once '../../db.php';

$sqls = [];
//
$sqls[] = "ALTER TABLE  account_group add p_create_self_site tinyint not null default 0 comment '创建用户的默认团队'";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;