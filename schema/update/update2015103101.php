<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "alter table xxt_merchant_page change type type varchar(30) not null";
$sqls[] = "alter table xxt_merchant_catelog add pages text";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo "end update " . __FILE__ . PHP_EOL;