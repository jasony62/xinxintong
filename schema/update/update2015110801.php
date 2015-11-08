<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "alter table xxt_merchant_shop add buyer_api text after approved";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo "end update " . __FILE__ . PHP_EOL;