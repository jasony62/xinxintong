<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "alter table xxt_group add source_app varchar(255) not null default '' after scenario";
$sqls[] = "alter table xxt_group add last_sync_at int not null after source_app";
$sqls[] = "alter table xxt_group_result add state tinyint not null default 1";
$sqls[] = "ALTER TABLE `xxt_group_result` DROP PRIMARY KEY, ADD PRIMARY KEY( `aid`, `enroll_key`, `state`)";
$sqls[] = "alter table xxt_group_player add round_id varchar(32) not null default ''";
$sqls[] = "alter table xxt_group_player add round_title varchar(40) not null default ''";
$sqls[] = "alter table xxt_group_player add draw_at int not null";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;