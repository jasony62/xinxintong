<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "alter table xxt_contribute add template_body text after params";
$sqls[] = "alter table xxt_contribute change authapis initiator_schemas text";
$sqls[] = "alter table xxt_site add shift2pc_page_id int not null default 0";
$sqls[] = "alter table xxt_task change mpid siteid varchar(32) not null";
$sqls[] = "alter table xxt_task change fid userid varchar(40) not null";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo "end update " . __FILE__ . PHP_EOL;