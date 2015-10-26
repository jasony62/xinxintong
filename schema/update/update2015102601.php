<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "alter table xxt_merchant_order_sku add cate_id int not null after oid";
$sqls[] = "alter table xxt_merchant_order_sku add cate_sku_id int not null after cate_id";
$sqls[] = "update xxt_merchant_order_sku s,xxt_merchant_product_sku p set s.cate_sku_id=p.cate_sku_id where s.sku_id=p.id";
$sqls[] = "alter table xxt_merchant_order_sku add prod_id int not null after cate_sku_id";
$sqls[] = "update xxt_merchant_order_sku set prod_id=product_id";
$sqls[] = "alter table xxt_merchant_order_sku drop product_id";
$sqls[] = "update xxt_merchant_order_sku s,xxt_merchant_product p set s.cate_id=p.cate_id where s.prod_id=p.id";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo "end update " . __FILE__ . PHP_EOL;