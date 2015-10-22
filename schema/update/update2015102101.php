<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "ALTER TABLE xxt_merchant_catelog_sku ADD can_autogen char(1) not null default 'N' after require_pay";
$sqls[] = "ALTER TABLE xxt_merchant_catelog_sku ADD autogen_rule text after can_autogen";
$sqls[] = "update xxt_merchant_catelog_sku set autogen_rule='{}'";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo "end update " . __FILE__ . PHP_EOL;