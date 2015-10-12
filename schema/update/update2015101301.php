<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "ALTER TABLE xxt_merchant_page ADD name varchar(70) not null default '' after type";
$sqls[] = "ALTER TABLE xxt_merchant_page ADD summary varchar(240) not null default '' after title";
$sqls[] = "update xxt_merchant_page set name=title";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo "end update " . __FILE__ . PHP_EOL;