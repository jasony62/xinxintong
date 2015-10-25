<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "alter table xxt_merchant_order add products text after sid";
$sqls[] = "alter table xxt_merchant_order drop product_id";
$sqls[] = "alter table xxt_merchant_order drop product_name";
$sqls[] = "alter table xxt_merchant_order drop product_img";
$sqls[] = "alter table xxt_merchant_order drop product_price";
$sqls[] = "alter table xxt_merchant_order drop product_sku";
$sqls[] = "alter table xxt_merchant_order drop product_count";
$sqls[] = "alter table xxt_merchant_order_sku add product_id int not null after oid";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo "end update " . __FILE__ . PHP_EOL;