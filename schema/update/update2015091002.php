<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "ALTER TABLE xxt_lottery drop column extra_css";
$sqls[] = "ALTER TABLE xxt_lottery drop column extra_ele";
$sqls[] = "ALTER TABLE xxt_lottery drop column extra_js";
$sqls[] = "ALTER TABLE xxt_lottery add  pretask char(1) not null default 'Y' after authapis";
$sqls[] = "ALTER TABLE xxt_lottery add  pretaskdesc text after pretask";
$sqls[] = "ALTER TABLE xxt_lottery add  pretaskcount char(1) not null default 'F' after pretaskdesc";
$sqls[] = "ALTER TABLE xxt_lottery drop column precondition";
$sqls[] = "ALTER TABLE xxt_lottery drop column preactivity";
$sqls[] = "ALTER TABLE xxt_lottery drop column preactivitycount";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo "end update " . __FILE__ . PHP_EOL;