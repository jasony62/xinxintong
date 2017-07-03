<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "alter table xxt_site_wx add title varchar(255) not null default '' after by_platform";
$sqls[] = "update xxt_site_wx wx,xxt_site s set wx.title=s.name where wx.siteid=s.id";
//
$sqls[] = "alter table xxt_site_yx add title varchar(255) not null default '' after by_platform";
$sqls[] = "update xxt_site_yx yx,xxt_site s set yx.title=s.name where yx.siteid=s.id";
//
$sqls[] = "alter table xxt_site_qy add title varchar(255) not null default '' after create_at";
$sqls[] = "update xxt_site_qy qy,xxt_site s set qy.title=s.name where qy.siteid=s.id";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;