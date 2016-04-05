<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "alter table xxt_contribute add siteid varchar(32) not null default '' after mpid";
$sqls[] = "alter table xxt_contribute add creater_name varchar(255) not null default '' after creater";
$sqls[] = "alter table xxt_contribute add creater_src char(1) after creater_name";
$sqls[] = "alter table xxt_contribute add modifier varchar(40) not null default '' after create_at";
$sqls[] = "alter table xxt_contribute add modifier_name varchar(255) not null default '' after modifier";
$sqls[] = "alter table xxt_contribute add modifier_src char(1) after modifier_name";
$sqls[] = "alter table xxt_contribute add modify_at int not null after modifier_src";
$sqls[] = "alter table xxt_contribute_user add siteid varchar(32) not null default '' after mpid";
$sqls[] = "alter table xxt_coin_rule add siteid varchar(32) not null default '' after mpid";
$sqls[] = "alter table xxt_coin_log add siteid varchar(32) not null default '' after mpid";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo "end update " . __FILE__ . PHP_EOL;