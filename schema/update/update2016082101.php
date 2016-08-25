<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "alter table xxt_site_account add wx_openid varchar(255) not null default '' after ufrom";
$sqls[] = "alter table xxt_site_account add yx_openid varchar(255) not null default '' after wx_openid";
$sqls[] = "alter table xxt_site_account add qy_openid varchar(255) not null default '' after yx_openid";
$sqls[] = "update xxt_site_account a,xxt_site_wxfan w set a.wx_openid=w.openid where a.uid=w.userid";
$sqls[] = "update xxt_site_account a,xxt_site_yxfan y set a.yx_openid=y.openid where a.uid=y.userid";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;