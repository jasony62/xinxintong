<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "alter table xxt_signin add use_site_header char(1) not null default 'Y' after data_schemas";
$sqls[] = "alter table xxt_signin add use_site_footer char(1) not null default 'Y' after use_site_header";
$sqls[] = "alter table xxt_signin add use_mission_header char(1) not null default 'Y' after use_site_footer";
$sqls[] = "alter table xxt_signin add use_mission_footer char(1) not null default 'Y' after use_mission_header";
$sqls[] = "alter table xxt_group add use_site_header char(1) not null default 'Y' after page_code_name";
$sqls[] = "alter table xxt_group add use_site_footer char(1) not null default 'Y' after use_site_header";
$sqls[] = "alter table xxt_group add use_mission_header char(1) not null default 'Y' after use_site_footer";
$sqls[] = "alter table xxt_group add use_mission_footer char(1) not null default 'Y' after use_mission_header";
$sqls[] = "alter table xxt_article add use_mission_header char(1) not null default 'Y' after use_site_footer";
$sqls[] = "alter table xxt_article add use_mission_footer char(1) not null default 'Y' after use_mission_header";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;