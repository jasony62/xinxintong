<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "ALTER TABLE xxt_site add home_heading_pic text after home_page_name";
$sqls[] = "ALTER TABLE xxt_site add home_mobile_layout varchar(20) default 'g1_g2' after home_heading_pic";
$sqls[] = "ALTER TABLE xxt_site add can_contribute char(1) not null default 'N'";
$sqls[] = "ALTER TABLE xxt_site add can_subscribe char(1) not null default 'Y'";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;