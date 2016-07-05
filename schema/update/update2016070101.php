<?php
require_once '../../db.php';
$sqls = array();
//
$sqls[] = "alter table xxt_site_account change coin coin int not null default 0";
$sqls[] = "alter table xxt_site_account change coin_last_at coin_last_at int not null default 0";
$sqls[] = "alter table xxt_site_account change coin_day coin_day int not null default 0";
$sqls[] = "alter table xxt_site_account change coin_week coin_week int not null default 0";
$sqls[] = "alter table xxt_site_account change coin_month coin_month int not null default 0";
$sqls[] = "alter table xxt_site_account change coin_year coin_year int not null default 0";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;