<?php
require_once '../../db.php';

$sqls = [];
$sqls[] = "alter table xxt_coin_log add userid varchar(255) not null after payee";
$sqls[] = "alter table xxt_coin_log add last_row char(1) not null default 'Y'";
$sqls[] = "alter table xxt_coin_rule add matter_type varchar(20) not null";
$sqls[] = "alter table xxt_coin_rule add matter_filter varchar(40) not null default '*'";
$sqls[] = "alter table xxt_coin_rule add actor_delta int not null default 0";
$sqls[] = "alter table xxt_coin_rule add creator_delta int not null default 0";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;