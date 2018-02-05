<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "ALTER TABLE xxt_site_member_schema add ext_attrs text null after extattr";
$sqls[] = "ALTER TABLE xxt_site_member_schema drop entry_statement";
$sqls[] = "ALTER TABLE xxt_site_member_schema drop acl_statement";
$sqls[] = "ALTER TABLE xxt_site_member_schema drop notpass_statement";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;