<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "alter table xxt_merchant_shop add siteid varchar(32) not null after mpid";
$sqls[] = "alter table xxt_merchant_page add siteid varchar(32) not null after mpid";
$sqls[] = "alter table xxt_merchant_staff add siteid varchar(32) not null after mpid";
$sqls[] = "alter table xxt_merchant_catelog add siteid varchar(32) not null after mpid";
$sqls[] = "alter table xxt_merchant_catelog_property add siteid varchar(32) not null after mpid";
$sqls[] = "alter table xxt_merchant_catelog_property_value add siteid varchar(32) not null after mpid";
$sqls[] = "alter table xxt_merchant_catelog_sku add siteid varchar(32) not null after mpid";
$sqls[] = "alter table xxt_merchant_catelog_sku_value add siteid varchar(32) not null after mpid";
$sqls[] = "alter table xxt_merchant_product add siteid varchar(32) not null after mpid";
$sqls[] = "alter table xxt_merchant_product_sku add siteid varchar(32) not null after mpid";
$sqls[] = "alter table xxt_merchant_product_gensku_log add siteid varchar(32) not null after mpid";
$sqls[] = "alter table xxt_merchant_group add siteid varchar(32) not null after mpid";
$sqls[] = "alter table xxt_merchant_group_product add siteid varchar(32) not null after mpid";
$sqls[] = "alter table xxt_merchant_order_property add siteid varchar(32) not null after mpid";
$sqls[] = "alter table xxt_merchant_order_feedback_property add siteid varchar(32) not null after mpid";
$sqls[] = "alter table xxt_merchant_order add siteid varchar(32) not null after mpid";
$sqls[] = "alter table xxt_merchant_order_sku add siteid varchar(32) not null after mpid";
$sqls[] = 'alter table xxt_merchant_shop add modifier varchar(40) not null after reviser';
$sqls[] = "alter table xxt_merchant_shop add modifier_name varchar(255) not null default '' after modifier";
$sqls[] = "alter table xxt_merchant_shop add modify_at int not null after modifier_name";

$sqls[] = "update xxt_merchant_shop set siteid=mpid";
$sqls[] = "update xxt_merchant_page set siteid=mpid";
$sqls[] = "update xxt_merchant_staff set siteid=mpid";
$sqls[] = "update xxt_merchant_catelog set siteid=mpid";
$sqls[] = "update xxt_merchant_catelog_property set siteid=mpid";
$sqls[] = "update xxt_merchant_catelog_property_value set siteid=mpid";
$sqls[] = "update xxt_merchant_catelog_sku set siteid=mpid";
$sqls[] = "update xxt_merchant_catelog_sku_value set siteid=mpid";
$sqls[] = "update xxt_merchant_product set siteid=mpid";
$sqls[] = "update xxt_merchant_product_sku set siteid=mpid";
$sqls[] = "update xxt_merchant_product_gensku_log set siteid=mpid";
$sqls[] = "update xxt_merchant_group set siteid=mpid";
$sqls[] = "update xxt_merchant_group_product set siteid=mpid";
$sqls[] = "update xxt_merchant_order_property set siteid=mpid";
$sqls[] = "update xxt_merchant_order_feedback_property set siteid=mpid";
$sqls[] = "update xxt_merchant_order set siteid=mpid";
$sqls[] = "update xxt_merchant_order_sku set siteid=mpid";

$sqls[] = "alter table xxt_tmplmsg add siteid varchar(32) not null after id";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo "end update " . __FILE__ . PHP_EOL;