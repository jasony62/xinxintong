<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "insert into account_in_group(account_uid,group_id) select unionid,1 from xxt_site_registration";
//
$sqls[] = "alter table xxt_site_favor change userid unionid varchar(32) not null";
$sqls[] = "alter table xxt_site_subscriber change userid unionid varchar(32) not null";
$sqls[] = "alter table xxt_site_subscription change userid unionid varchar(32) not null";
$sqls[] = "alter table xxt_site_subscriber drop from_siteid";
$sqls[] = "alter table xxt_site_subscriber drop from_site_name";
$sqls[] = "alter table xxt_site_subscriber drop creater";
$sqls[] = "alter table xxt_site_subscriber drop creater_name";
$sqls[] = "alter table xxt_site_subscription drop from_siteid";
$sqls[] = "alter table xxt_site_subscription drop from_site_name";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;