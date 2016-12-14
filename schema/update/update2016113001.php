<?php
require_once '../../db.php';

$sqls = [];
$sqls[] = "alter table account add coin int not null default 0";
$sqls[] = "alter table account add coin_last_at int not null default 0";
$sqls[] = "alter table account add coin_day int not null default 0";
$sqls[] = "alter table account add coin_week int not null default 0";
$sqls[] = "alter table account add coin_month int not null default 0";
$sqls[] = "alter table account add coin_year int not null default 0";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;