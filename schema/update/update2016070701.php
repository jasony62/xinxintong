<?php
require_once '../../db.php';
$sqls = array();
//
$sqls[] = "alter table xxt_wall change mpid mpid varchar(32) not null default ''";
$sqls[] = "alter table xxt_wall add siteid varchar(32) not null";
$sqls[] = "alter table xxt_wall_page change mpid mpid varchar(32) not null default ''";
$sqls[] = "alter table xxt_wall_page add siteid varchar(32) not null";
$sqls[] = "alter table xxt_wall_enroll change mpid mpid varchar(32) not null default ''";
$sqls[] = "alter table xxt_wall_enroll add siteid varchar(32) not null";
$sqls[] = "alter table xxt_wall_log change mpid mpid varchar(32) not null default ''";
$sqls[] = "alter table xxt_wall_log add siteid varchar(32) not null";
//
$sqls[] = "alter table xxt_addressbook change mpid mpid varchar(32) not null default ''";
$sqls[] = "alter table xxt_addressbook add siteid varchar(32) not null";
$sqls[] = "alter table xxt_ab_dept change mpid mpid varchar(32) not null default ''";
$sqls[] = "alter table xxt_ab_dept add siteid varchar(32) not null";
$sqls[] = "alter table xxt_ab_tag change mpid mpid varchar(32) not null default ''";
$sqls[] = "alter table xxt_ab_tag add siteid varchar(32) not null";
$sqls[] = "alter table xxt_ab_person change mpid mpid varchar(32) not null default ''";
$sqls[] = "alter table xxt_ab_person add siteid varchar(32) not null";
$sqls[] = "alter table xxt_ab_person_dept change mpid mpid varchar(32) not null default ''";
$sqls[] = "alter table xxt_ab_person_dept add siteid varchar(32) not null";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;