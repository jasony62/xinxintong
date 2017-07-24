<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "alter table xxt_site_member_schema change mission_id matter_id varchar(40) not null default ''";
$sqls[] = "alter table xxt_site_member_schema add matter_type varchar(20) default '' after matter_id";
$sqls[] = "update xxt_site_member_schema set matter_id = '' where matter_id=0";
$sqls[] = "update xxt_site_member_schema set matter_type = 'mission'";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;