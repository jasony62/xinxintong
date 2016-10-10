<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "alter table xxt_tmplmsg_param change pname pname varchar(128) not null default ''";
$sqls[] = "alter table xxt_tmplmsg_param change plabel plabel varchar(255) not null default ''";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;