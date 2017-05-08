<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "alter table xxt_wall add mission_id int not null default 0 after summary";
$sqls[] = "alter table xxt_wall add mission_phase_id varchar(13) not null default '' after mission_id";
$sqls[] = "alter table xxt_wall add creater_name varchar(255) not null default '' after creater";
$sqls[] = "alter table xxt_wall add state tinyint not null default 1";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;