<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "alter table xxt_site_member add unionid varchar(32) not null default '' after userid";
//
$sqls[] = "update xxt_site_member m,xxt_site_account a set m.unionid=a.unionid where m.userid=a.uid";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;