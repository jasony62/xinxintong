<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "alter table xxt_enroll add can_coinpay char(1) not null default 'N' after can_discuss";
$sqls[] = "alter table xxt_enroll add can_siteuser char(1) not null default 'N' after can_coinpay";
$sqls[] = "alter table xxt_article change can_siteuser can_siteuser char(1) not null default 'Y'";
$sqls[] = "update xxt_article set can_siteuser='Y'";
$sqls[] = "alter table xxt_enroll change can_siteuser can_siteuser char(1) not null default 'Y'";
$sqls[] = "update xxt_enroll set can_siteuser='Y'";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;