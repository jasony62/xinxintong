<?php
require_once '../../db.php';
$sqls = array();
//
$sqls[] = "alter table xxt_log_matter_read change mpid mpid varchar(32) not null default ''";
$sqls[] = "alter table xxt_log_matter_read change vid vid varchar(32) not null default ''";
$sqls[] = "alter table xxt_log_matter_read change openid openid varchar(255) not null default ''";
$sqls[] = "alter table xxt_log_matter_read change nickname nickname varchar(255) not null default ''";
//
$sqls[] = "alter table xxt_log_matter_share change mpid mpid varchar(32) not null default ''";
$sqls[] = "alter table xxt_log_tmplmsg change mpid mpid varchar(32) not null default ''";
//
$sqls[] = "alter table xxt_log_user_action change mpid mpid varchar(32) not null default ''";
$sqls[] = "alter table xxt_log_user_action change vid vid varchar(32) not null default ''";
$sqls[] = "alter table xxt_log_user_action change openid openid varchar(255) not null default ''";
$sqls[] = "alter table xxt_log_user_action change nickname nickname varchar(255) not null default ''";
//
$sqls[] = "alter table xxt_log_user_matter change mpid mpid varchar(32) not null default ''";
$sqls[] = "alter table xxt_log_user_matter change openid openid varchar(255) not null default ''";
$sqls[] = "alter table xxt_log_user_matter change nickname nickname varchar(255) not null default ''";
//
$sqls[] = "alter table xxt_log_matter_action change mpid mpid varchar(32) not null default ''";
//
$sqls[] = "alter table xxt_log_matter_op change matter_title matter_title varchar(70) not null default ''";
$sqls[] = "alter table xxt_log_matter_op change matter_summary matter_summary varchar(240) not null default ''";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;