<?php
require_once '../../db.php';
$sqls = array();
//
$sqls[] = "alter table xxt_addressbook change siteid siteid varchar(32) not null default ''";
$sqls[] = "alter table xxt_addressbook change summary summary varchar(240) not null default ''";
$sqls[] = "alter table xxt_ab_dept change siteid siteid varchar(32) not null default ''";
$sqls[] = "alter table xxt_ab_tag change siteid siteid varchar(32) not null default ''";
$sqls[] = "alter table xxt_ab_person change siteid siteid varchar(32) not null default ''";
$sqls[] = "alter table xxt_ab_person_dept change siteid siteid varchar(32) not null default ''";
//
$sqls[] = "alter table xxt_wall change siteid siteid varchar(32) not null default ''";
$sqls[] = "alter table xxt_wall change summary summary varchar(240) not null default ''";
$sqls[] = "alter table xxt_wall_page change siteid siteid varchar(32) not null default ''";
$sqls[] = "alter table xxt_wall_enroll change siteid siteid varchar(32) not null default ''";
$sqls[] = "alter table xxt_wall_log change siteid siteid varchar(32) not null default ''";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;