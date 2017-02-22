<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "ALTER TABLE  `xxt_site_member_schema` ADD  `qy_ab`  char(1) default 'N' COMMENT  '同步企业号通信录认证ID'";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;