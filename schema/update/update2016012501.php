<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "alter table xxt_merchant_catelog add finish_order_tmplmsg int not null default 0 after feedback_order_tmplmsg";
$sqls[] = "alter table xxt_merchant_catelog add cancel_order_tmplmsg int not null default 0 after finish_order_tmplmsg";
$sqls[] = "alter table xxt_merchant_catelog add cus_cancel_order_tmplmsg int not null default 0 after cancel_order_tmplmsg";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo "end update " . __FILE__ . PHP_EOL;