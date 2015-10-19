<?php
require_once '../../db.php';

$sqls = array();
$sql = "create table if not exists xxt_merchant_order_sku(";
$sql .= "id int not null auto_increment";
$sql .= ",mpid varchar(32) not null";
$sql .= ",sid varchar(32) not null";
$sql .= ",oid int not null";
$sql .= ",sku_id int not null";
$sql .= ",sku_price int not null default 0";
$sql .= ",sku_count int not null default 1";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;
$sqls[] = "ALTER TABLE xxt_merchant_catelog ADD used char(1) not null default 'N'";
$sqls[] = "ALTER TABLE xxt_merchant_catelog ADD disabled char(1) not null default 'N'";
$sqls[] = "ALTER TABLE xxt_merchant_catelog ADD active char(1) not null default 'N'";
$sqls[] = "ALTER TABLE xxt_merchant_product ADD used char(1) not null default 'N'";
$sqls[] = "ALTER TABLE xxt_merchant_product ADD disabled char(1) not null default 'N'";
$sqls[] = "ALTER TABLE xxt_merchant_product ADD active char(1) not null default 'N'";
$sqls[] = "ALTER TABLE xxt_merchant_product_sku ADD used char(1) not null default 'N'";
$sqls[] = "ALTER TABLE xxt_merchant_product_sku ADD disabled char(1) not null default 'N'";
$sqls[] = "ALTER TABLE xxt_merchant_product_sku ADD active char(1) not null default 'N'";
$sqls[] = "ALTER TABLE xxt_merchant_catelog_property ADD used char(1) not null default 'N'";
$sqls[] = "ALTER TABLE xxt_merchant_catelog_property ADD disabled char(1) not null default 'N'";
$sqls[] = "ALTER TABLE xxt_merchant_order_property ADD used char(1) not null default 'N'";
$sqls[] = "ALTER TABLE xxt_merchant_order_property ADD disabled char(1) not null default 'N'";
$sqls[] = "ALTER TABLE xxt_merchant_order_feedback_property ADD used char(1) not null default 'N'";
$sqls[] = "ALTER TABLE xxt_merchant_order_feedback_property ADD disabled char(1) not null default 'N'";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo "end update " . __FILE__ . PHP_EOL;