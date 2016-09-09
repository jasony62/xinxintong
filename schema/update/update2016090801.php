<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "alter table xxt_call_menu_yx change matter_type matter_type varchar(20) not null default ''";
$sqls[] = "alter table xxt_call_menu_yx change matter_id matter_id varchar(40) not null default ''";
$sqls[] = "alter table xxt_call_menu_wx change matter_type matter_type varchar(20) not null default ''";
$sqls[] = "alter table xxt_call_menu_wx change matter_id matter_id varchar(40) not null default ''";
$sqls[] = "alter table xxt_call_menu_qy change matter_type matter_type varchar(20) not null default ''";
$sqls[] = "alter table xxt_call_menu_qy change matter_id matter_id varchar(40) not null default ''";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;