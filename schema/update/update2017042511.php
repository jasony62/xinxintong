<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "alter table xxt_article add copy_num int not null default 0 after download_num";
$sqls[] = "alter table xxt_article add from_mode char(1) not null default 'O'";
$sqls[] = "alter table xxt_article add from_siteid varchar(32) not null default ''";
$sqls[] = "alter table xxt_article add from_id int not null default 0";
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;