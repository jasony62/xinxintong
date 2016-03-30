<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "alter table xxt_log_matter_op change site_id siteid varchar(32) not null";
$sqls[] = "alter table xxt_news add siteid varchar(32) not null default '' after mpid";
$sqls[] = "alter table xxt_news add modifier varchar(40) not null default '' after creater_src";
$sqls[] = "alter table xxt_news add modifier_name varchar(255) not null default '' after modifier";
$sqls[] = "alter table xxt_news add modifier_src char(1) after modifier_name";
$sqls[] = "alter table xxt_news add modify_at int not null after modifier_src";
$sqls[] = "alter table xxt_channel add siteid varchar(32) not null default '' after mpid";
$sqls[] = "alter table xxt_channel add modifier varchar(40) not null default '' after creater_src";
$sqls[] = "alter table xxt_channel add modifier_name varchar(255) not null default '' after modifier";
$sqls[] = "alter table xxt_channel add modifier_src char(1) after modifier_name";
$sqls[] = "alter table xxt_channel add modify_at int not null after modifier_src";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo "end update " . __FILE__ . PHP_EOL;