<?php
require_once '../../db.php';

$sqls = [];
$sqls[] = "alter table xxt_coin_log add trans_no varchar(32) not null default ''";
$sqls[] = "alter table xxt_article add can_coinpay char(1) not null default 'N' after can_discuss";
$sqls[] = "alter table xxt_article add can_siteuser char(1) not null default 'N' after can_coinpay";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;