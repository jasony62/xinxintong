<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "alter table xxt_site_qyfan add userid varchar(40) not null default '' after forbidden";
$sqls[] = "update xxt_site_qyfan q,xxt_site_account a set q.userid=a.uid where q.openid=a.qy_openid";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;