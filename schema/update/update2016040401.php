<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "alter table xxt_site_admin change site_id siteid varchar(32) not null default ''";
$sqls[] = "alter table xxt_site_account change site_id siteid varchar(32) not null default ''";
$sqls[] = "alter table xxt_site_favor change site_id siteid varchar(32) not null default ''";
$sqls[] = "alter table xxt_log_matter_op change site_id siteid varchar(32) not null default ''";
$sqls[] = "alter table xxt_article_review_log add siteid varchar(32) not null default '' after mpid";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo "end update " . __FILE__ . PHP_EOL;