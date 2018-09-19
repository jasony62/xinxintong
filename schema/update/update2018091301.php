<?php
require_once '../../db.php';

$sqls = [];
//
$sqls[] = "ALTER TABLE xxt_site_account drop assoc_id";
$sqls[] = "ALTER TABLE xxt_site_account drop uname";
$sqls[] = "ALTER TABLE xxt_site_account drop password";
$sqls[] = "ALTER TABLE xxt_site_account drop salt";
$sqls[] = "ALTER TABLE xxt_site_account drop email";
$sqls[] = "ALTER TABLE xxt_site_account drop mobile";
$sqls[] = "ALTER TABLE xxt_site_account drop is_first_login";

//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;