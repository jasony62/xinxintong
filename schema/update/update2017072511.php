<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "alter table xxt_tag add creater varchar(40) not null default '' after mpid";
$sqls[] = "alter table xxt_tag add creater_name varchar(255) not null default '' after creater";
$sqls[] = "alter table xxt_tag add create_at int not null default 0 after creater_name";
$sqls[] = "alter table xxt_tag add sum int not null default 0";
$sqls[] = "alter table xxt_tag add seq int not null default 1";
$sqls[] = "alter table xxt_tag add sub_type char(1) not null default 'M'";
$sqls[] = "alter table xxt_tag drop index tag";
$sqls[] = "alter table xxt_tag add UNIQUE KEY `tag` (mpid,siteid,title,sub_type)";
//
$sqls[] = "alter table xxt_article add matter_cont_tag varchar(255) not null default ''";
$sqls[] = "alter table xxt_article add matter_mg_tag varchar(255) not null default ''";

$sqls[] = "alter table xxt_enroll add matter_mg_tag varchar(255) not null default ''";

$sqls[] = "alter table xxt_signin add matter_mg_tag varchar(255) not null default ''";

$sqls[] = "alter table xxt_group add matter_mg_tag varchar(255) not null default ''";

$sqls[] = "alter table xxt_mission add matter_mg_tag varchar(255) not null default ''";

$sqls[] = "alter table xxt_lottery add matter_mg_tag varchar(255) not null default ''";

$sqls[] = "alter table xxt_wall add matter_mg_tag varchar(255) not null default ''";

$sqls[] = "alter table xxt_news add matter_mg_tag varchar(255) not null default ''";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;