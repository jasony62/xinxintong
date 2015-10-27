<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "alter table xxt_merchant_catelog add pattern varchar(20) not null default 'basic' after parent_cate_id";
$sqls[] = "alter table xxt_merchant_page add cate_id int not null default 0 after sid";
$sqls[] = "alter table xxt_merchant_page add prod_id int not null default 0 after cate_id";
$sqls[] = "alter table xxt_merchant_product_sku add summary text after modify_at";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo "end update " . __FILE__ . PHP_EOL;