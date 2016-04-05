<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "alter table xxt_lottery add siteid varchar(32) not null default '' after mpid";
$sqls[] = "alter table xxt_lottery add creater_name varchar(255) not null default '' after creater";
$sqls[] = "alter table xxt_lottery add creater_src char(1) after creater_name";
$sqls[] = "alter table xxt_lottery add modifier varchar(40) not null default '' after create_at";
$sqls[] = "alter table xxt_lottery add modifier_name varchar(255) not null default '' after modifier";
$sqls[] = "alter table xxt_lottery add modifier_src char(1) after modifier_name";
$sqls[] = "alter table xxt_lottery add modify_at int not null after modifier_src";
$sqls[] = "alter table xxt_lottery_task add siteid varchar(32) not null default '' after mpid";
$sqls[] = "alter table xxt_lottery_task_log add userid varchar(40) not null default '' after tid";
$sqls[] = "alter table xxt_lottery_task_log add nickname varchar(255) not null default '' after userid";
$sqls[] = "alter table xxt_lottery_award add siteid varchar(32) not null default '' after mpid";
$sqls[] = "alter table xxt_lottery_plate add siteid varchar(32) not null default '' after mpid";
$sqls[] = "alter table xxt_lottery_log add siteid varchar(32) not null default '' after mpid";
$sqls[] = "alter table xxt_lottery_log add userid varchar(40) not null default '' after lid";
$sqls[] = "alter table xxt_lottery_log add nickname varchar(255) not null default '' after userid";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo "end update " . __FILE__ . PHP_EOL;