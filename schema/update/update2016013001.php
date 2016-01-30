<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "alter table xxt_fans add coin_last_at int not null";
$sqls[] = "alter table xxt_fans add coin_day int not null";
$sqls[] = "alter table xxt_fans add coin_week int not null";
$sqls[] = "alter table xxt_fans add coin_month int not null";
$sqls[] = "alter table xxt_fans add coin_year int not null";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo "end update " . __FILE__ . PHP_EOL;