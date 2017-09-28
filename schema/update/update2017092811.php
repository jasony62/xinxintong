<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "delete from xxt_site_notice where tmplmsg_config_id = 0";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;