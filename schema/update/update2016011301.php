<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "alter table xxt_coin_rule add objid varchar(255) not null default '*' after act";
$sqls[] = "alter table xxt_coin_log add nickname varchar(255) not null after payee";
$sqls[] = "ALTER TABLE `xxt_coin_log` CHANGE `detal` `delta` INT(11) NOT NULL";
$sqls[] = "ALTER TABLE `xxt_coin_rule` CHANGE `detal` `delta` INT(11) NOT NULL";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo "end update " . __FILE__ . PHP_EOL;