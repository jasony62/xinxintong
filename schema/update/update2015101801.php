<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "ALTER TABLE xxt_merchant_catelog_sku ADD require_pay char(1) not null default 'N' after has_validity";
$sqls[] = "ALTER TABLE xxt_merchant_catelog_sku ADD used char(1) not null default 'N'";
$sqls[] = "ALTER TABLE xxt_merchant_catelog_sku ADD disabled char(1) not null default 'N'";
$sqls[] = "ALTER TABLE xxt_merchant_product_sku ADD cate_sku_id int not null after prod_id";
$sqls[] = "ALTER TABLE `xxt_merchant_staff` CHANGE `shopid` `sid` INT NOT NULL";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo "end update " . __FILE__ . PHP_EOL;