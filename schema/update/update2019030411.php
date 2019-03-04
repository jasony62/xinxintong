<?php
require_once '../../db.php';

$sqls = [];
//
$sqls[] = "";
$sqls[] = "";
$sqls[] = "ALTER TABLE xxt_matter_download_log add matter_type varchar(20) not null default '' after matter_id";
$sqls[] = "update xxt_matter_download_log set matter_type = 'article'";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;