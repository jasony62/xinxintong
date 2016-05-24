<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "alter table xxt_mission add header_page_name varchar(13) not null default '' after end_at";
$sqls[] = "alter table xxt_mission add footer_page_name varchar(13) not null default '' after header_page_name";
$sqls[] = "alter table xxt_enroll add use_mission_header char(1) not null default 'Y' after use_site_footer";
$sqls[] = "alter table xxt_enroll add use_mission_footer char(1) not null default 'Y' after use_mission_header";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;