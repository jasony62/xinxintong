<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "alter table xxt_merchant_product_sku add unlimited_quantity char(1) not null default 'N' after icon_url";
$sqls[] = "alter table xxt_merchant_product_sku add required char(1) not null default 'N' after product_code";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo "end update " . __FILE__ . PHP_EOL;