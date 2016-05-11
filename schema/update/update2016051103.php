<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "alter table xxt_article add use_site_header char(1) not null default 'Y'";
$sqls[] = "alter table xxt_article add use_site_footer char(1) not null default 'Y'";
$sqls[] = "alter table xxt_enroll add use_site_header char(1) not null default 'Y'";
$sqls[] = "alter table xxt_enroll add use_site_footer char(1) not null default 'Y'";
$sqls[] = "alter table xxt_site_member_schema add passed_url text after url";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo "end update " . __FILE__ . PHP_EOL;