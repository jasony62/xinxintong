<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "alter table xxt_shop_matter add scenario varchar(255) not null default '' after matter_type";
$sqls[] = "alter table xxt_shop_matter_acl add scenario varchar(255) not null default '' after matter_type";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;