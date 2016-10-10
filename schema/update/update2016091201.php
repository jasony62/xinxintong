<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "alter table xxt_signin_round change start_at start_at int not null default 0";
$sqls[] = "alter table xxt_signin_round change end_at end_at int not null default 0";
$sqls[] = "alter table xxt_group change last_sync_at last_sync_at int not null default 0";
$sqls[] = "alter table xxt_tmplmsg change templateid templateid varchar(128) not null default ''";
$sqls[] = "alter table xxt_merchant_shop change reviser reviser varchar(40) not null default ''";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;