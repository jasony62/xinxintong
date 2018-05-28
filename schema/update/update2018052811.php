<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "ALTER TABLE xxt_mission_user add last_topic_at int not null default 0 after signin_num";
$sqls[] = "ALTER TABLE xxt_mission_user add topic_num int not null default 0 after last_topic_at";
//
$sqls[] = "ALTER TABLE xxt_mission_user add do_repos_read_num int not null default 0 after topic_num";
$sqls[] = "ALTER TABLE xxt_mission_user add do_repos_read_elapse int not null default 0 after do_repos_read_num";
//
$sqls[] = "ALTER TABLE xxt_mission_user add do_topic_read_num int not null default 0 after do_repos_read_elapse";
$sqls[] = "ALTER TABLE xxt_mission_user add topic_read_num int not null default 0 after do_topic_read_num";
$sqls[] = "ALTER TABLE xxt_mission_user add do_topic_read_elapse int not null default 0 after topic_read_num";
$sqls[] = "ALTER TABLE xxt_mission_user add topic_read_elapse int not null default 0 after do_topic_read_elapse";

$sqls[] = "ALTER TABLE xxt_mission_user add do_cowork_read_num int not null default 0 after topic_read_elapse";
$sqls[] = "ALTER TABLE xxt_mission_user add cowork_read_num int not null default 0 after do_cowork_read_num";
$sqls[] = "ALTER TABLE xxt_mission_user add do_cowork_read_elapse int not null default 0 after cowork_read_num";
$sqls[] = "ALTER TABLE xxt_mission_user add cowork_read_elapse int not null default 0 after do_cowork_read_elapse";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;