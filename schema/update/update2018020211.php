<?php
require_once '../../db.php';

$sqls = array();
/**
 * 项目中的推荐内容
 */
//
$sqls[] = "ALTER TABLE xxt_plan add op_short_url_code char(4) not null default ''";
$sqls[] = "ALTER TABLE xxt_plan add rp_short_url_code char(4) not null default ''";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;