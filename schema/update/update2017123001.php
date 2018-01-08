<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "DROP TABLE xxt_pl_wx";
$sqls[] = "DROP TABLE xxt_pl_wxfan";
$sqls[] = "DROP TABLE xxt_pl_wxfangroup";
$sqls[] = "DROP TABLE xxt_pl_yx";
$sqls[] = "DROP TABLE xxt_pl_yxfan";
$sqls[] = "DROP TABLE xxt_pl_yxfangroup";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;