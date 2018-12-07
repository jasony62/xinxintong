<?php
require_once '../../db.php';

$sqls = [];
//
$sql[] = "drop table if exists xxt_merchant_shop";
$sql[] = "drop table if exists xxt_merchant_page";
$sql[] = "drop table if exists xxt_merchant_staff";
$sql[] = "drop table if exists xxt_merchant_catelog";
$sql[] = "drop table if exists xxt_merchant_catelog_property";
$sql[] = "drop table if exists xxt_merchant_catelog_property_value";
$sql[] = "drop table if exists xxt_merchant_catelog_sku";
$sql[] = "drop table if exists xxt_merchant_catelog_sku_value";
$sql[] = "drop table if exists xxt_merchant_product";
$sql[] = "drop table if exists xxt_merchant_product_sku";
$sql[] = "drop table if exists xxt_merchant_product_gensku_log";
$sql[] = "drop table if exists xxt_merchant_group";
$sql[] = "drop table if exists xxt_merchant_group_product";
$sql[] = "drop table if exists xxt_merchant_order_property";
$sql[] = "drop table if exists xxt_merchant_order_feedback_property";
$sql[] = "drop table if exists xxt_merchant_order";
$sql[] = "drop table if exists xxt_merchant_order_sku";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;