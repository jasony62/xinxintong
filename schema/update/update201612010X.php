<?php
require_once '../../db.php';

$sqls = [];
$sqls[] = "alter table xxt_wall add ufrom char(5) not null default ''";
$sqls[] = "alter table xxt_wall_enroll drop primary key";
$sqls[] = "alter table xxt_wall_enroll add primary key(openid)";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;