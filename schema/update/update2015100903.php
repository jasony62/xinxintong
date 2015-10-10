<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "ALTER TABLE xxt_merchant_catelog ADD submit_order_tmplmsg int not null default 0";
$sqls[] = "ALTER TABLE xxt_merchant_catelog ADD pay_order_tmplmsg int not null default 0";
$sqls[] = "ALTER TABLE xxt_merchant_catelog ADD feedback_order_tmplmsg int not null default 0";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo "end update " . __FILE__ . PHP_EOL;