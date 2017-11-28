<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "ALTER TABLE xxt_coin_rule drop mpid";
$sqls[] = "ALTER TABLE xxt_coin_rule drop objid";
$sqls[] = "ALTER TABLE xxt_coin_rule drop delta";
$sqls[] = "ALTER TABLE xxt_coin_rule add actor_overlap char(1) not null default 'A' after actor_delta";
//
$sqls[] = "ALTER TABLE xxt_coin_log drop mpid";
$sqls[] = "ALTER TABLE xxt_coin_log drop payee";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;