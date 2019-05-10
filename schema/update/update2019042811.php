<?php
require_once '../../db.php';

$sqls = [];
// mission
$sqls[] = "ALTER TABLE xxt_mission_user add do_rank_read_num int not null default 0 after cowork_read_elapse";
$sqls[] = "ALTER TABLE xxt_mission_user add do_rank_read_elapse int not null default 0 after do_rank_read_num";

// enroll_user
$sqls[] = "ALTER TABLE xxt_enroll_user add do_rank_read_num int not null default 0 after cowork_read_elapse";
$sqls[] = "ALTER TABLE xxt_enroll_user add do_rank_read_elapse int not null default 0 after do_rank_read_num";


foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;