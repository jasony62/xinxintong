<?php
require_once '../../db.php';
$sqls = array();
//
$sqls[] = "alter table xxt_pl_wx add plid varchar(32) not null after id";
$sqls[] = "alter table xxt_pl_wx drop creater";
$sqls[] = "alter table xxt_pl_yx add plid varchar(32) not null after id";
$sqls[] = "alter table xxt_pl_yx drop creater";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;