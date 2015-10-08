<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "ALTER TABLE xxt_mpsetting ADD wx_pay char(1) not null default 'N' after qy_updateab";
$sqls[] = "ALTER TABLE xxt_mpaccount ADD wx_mchid  varchar(32) not null default '' after wx_cardid";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo "end update " . __FILE__ . PHP_EOL;