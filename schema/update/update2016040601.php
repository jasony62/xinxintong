<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "alter table xxt_contribute_user add level int not null default 1";
$sqls[] = "alter table xxt_article_remark add siteid varchar(32) not null after mpid";
$sqls[] = "alter table xxt_article_remark add userid varchar(40) not null after siteid";
$sqls[] = "alter table xxt_article_score add siteid varchar(32) not null after mpid";
$sqls[] = "alter table xxt_article_score add userid varchar(40) not null after siteid";
$sqls[] = "alter table xxt_log_matter_read add siteid varchar(32) not null after id";
$sqls[] = "alter table xxt_log_matter_read add userid varchar(40) not null after siteid";
$sqls[] = "alter table xxt_log_matter_share add siteid varchar(32) not null after id";
$sqls[] = "alter table xxt_log_matter_share add userid varchar(40) not null after siteid";
$sqls[] = "alter table xxt_log_user_action add siteid varchar(32) not null after id";
$sqls[] = "alter table xxt_log_user_action add userid varchar(40) not null after siteid";
$sqls[] = "alter table xxt_log_matter_action add siteid varchar(32) not null after id";
$sqls[] = "alter table xxt_log_matter_action add userid varchar(40) not null after siteid";
$sqls[] = "alter table xxt_log_user_matter add siteid varchar(32) not null after id";
$sqls[] = "alter table xxt_log_user_matter add userid varchar(40) not null after siteid";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo "end update " . __FILE__ . PHP_EOL;