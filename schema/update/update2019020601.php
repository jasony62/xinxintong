<?php
require_once '../../db.php';

$sqls = [];
//
$sqls[] = "ALTER TABLE xxt_group_round change round_id team_id varchar(32) not null";
$sqls[] = "ALTER TABLE xxt_group_round change round_type team_type  char(1) not null default 'T'";
$sqls[] = "ALTER TABLE xxt_group_round RENAME xxt_group_team";
$sqls[] = "ALTER TABLE xxt_group_player change round_id team_id varchar(32) not null";
$sqls[] = "ALTER TABLE xxt_group_player change round_title team_title varchar(40) not null default ''";
$sqls[] = "ALTER TABLE xxt_group_player change role_rounds role_teams varchar(255) not null default ''";
$sqls[] = "ALTER TABLE xxt_group_player RENAME xxt_group_record";
$sqls[] = "ALTER TABLE xxt_group_player_data RENAME xxt_group_record_data";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;