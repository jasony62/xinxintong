<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "ALTER TABLE xxt_merchant_catelog_sku ADD has_validity char(1) not null default 'N' after name";
$sqls[] = "ALTER TABLE xxt_merchant_product_sku ADD validity_begin_at int not null default 0 after quantity";
$sqls[] = "ALTER TABLE xxt_merchant_product_sku ADD validity_end_at int not null default 0 after validity_begin_at";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo "end update " . __FILE__ . PHP_EOL;