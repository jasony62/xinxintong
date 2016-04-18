<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "alter table xxt_news add pic text after title";
$sqls[] = "alter table xxt_news add summary varchar(240) not null after pic";
$sqls[] = "alter table xxt_channel add pic text after title";
$sqls[] = "alter table xxt_channel add summary varchar(240) not null after pic";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo "end update " . __FILE__ . PHP_EOL;