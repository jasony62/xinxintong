<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "alter table xxt_site_account add ufrom varchar(20) not null default '' comment '用户来源'";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo "end update " . __FILE__ . PHP_EOL;