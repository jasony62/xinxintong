<?php
require_once '../../db.php';

$sqls = [];
$sqls[] = "alter table xxt_template change visible_scope visible_scope char(1) not null default 'S'";
$sqls[] = "alter table xxt_template add coin int not null default 0";
$sqls[] = "alter table xxt_lottery add pay_coin int not null default 0 after chance";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;