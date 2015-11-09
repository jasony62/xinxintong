<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "alter table xxt_merchant_product_sku add has_validity char(1) not null default 'N' after quantity";
$sqls[] = "update xxt_merchant_product_sku p,xxt_merchant_catelog_sku c set p.has_validity=c.has_validity where p.cate_sku_id=c.id";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo "end update " . __FILE__ . PHP_EOL;