<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "alter table xxt_site_wx add follow_page_id int not null default 0";
$sqls[] = "alter table xxt_site_yx add follow_page_id int not null default 0";
$sqls[] = "alter table xxt_site_qy add follow_page_id int not null default 0";
$sqls[] = "alter table xxt_site_member change openid identity varchar(255) not null";
$sqls[] = "alter table xxt_matter_acl add siteid varchar(32) not null after id";
$sqls[] = "alter table xxt_mission_matter add siteid varchar(32) not null after mpid";
$sqls[] = "alter table xxt_text add siteid varchar(32) not null after mpid";
$sqls[] = "alter table xxt_text add creater_name varchar(255) not null default '' after creater";
$sqls[] = "alter table xxt_text add creater_src char(1) default 'A' after creater_name";
$sqls[] = "alter table xxt_text add modifier varchar(40) not null default '' after create_at";
$sqls[] = "alter table xxt_text add modifier_name varchar(255) not null default '' after modifier";
$sqls[] = "alter table xxt_text add modifier_src char(1) default 'A' after modifier_name";
$sqls[] = "alter table xxt_text add modify_at int not null after modifier_src";
$sqls[] = "alter table xxt_text add title text";
$sqls[] = "alter table xxt_article_download_log add siteid varchar(32) not null after id";
$sqls[] = "alter table xxt_article_download_log add userid varchar(40) not null after siteid";
$sqls[] = "alter table xxt_link add siteid varchar(32) not null after mpid";
$sqls[] = "alter table xxt_link add creater_name varchar(255) not null default '' after creater";
$sqls[] = "alter table xxt_link add creater_src char(1) default 'A' after creater_name";
$sqls[] = "alter table xxt_link add modifier varchar(40) not null default '' after create_at";
$sqls[] = "alter table xxt_link add modifier_name varchar(255) not null default '' after modifier";
$sqls[] = "alter table xxt_link add modifier_src char(1) default 'A' after modifier_name";
$sqls[] = "alter table xxt_link add modify_at int not null after modifier_src";
$sqls[] = "alter table xxt_log_massmsg add siteid varchar(32) not null after id";
$sqls[] = "alter table xxt_log_tmplmsg add siteid varchar(32) not null after id";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo "end update " . __FILE__ . PHP_EOL;