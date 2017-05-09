<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "alter table xxt_site_favor add site_name varchar(50) not null after unionid";
$sqls[] = "update xxt_site_favor f,xxt_site s set f.site_name = s.name where f.siteid=s.id";

$sqls[] = "alter table xxt_site_friend_favor add from_site_name varchar(50) not null after matter_id";
$sqls[] = "update xxt_site_friend_favor f,xxt_site s set f.from_site_name = s.name where f.from_siteid=s.id";

$sqls[] = "alter table xxt_site_contribute add from_site_name varchar(50) not null after creater";
$sqls[] = "update xxt_site_contribute c,xxt_site s set c.from_site_name = s.name where c.from_siteid=s.id";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;