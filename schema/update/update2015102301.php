<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "ALTER TABLE xxt_merchant_product_sku ADD creater_src char(1) not null default 'A' after creater";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo "end update " . __FILE__ . PHP_EOL;