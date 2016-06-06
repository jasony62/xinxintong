<?php
require_once '../../db.php';
$sqls = array();
/**
 * 任务的阶段
 */
$sql = "create table if not exists xxt_mission_phase(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",mission_id int not null";
$sql .= ",phase_id varchar(13) not null";
$sql .= ",title varchar(70) not null";
$sql .= ",start_at int not null default 0"; // 开始时间
$sql .= ",end_at int not null default 0"; // 结束时间
$sql .= ",summary varchar(240) not null";
$sql .= ",seq int not null default 0";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;
//
$sqls[] = "alter table xxt_mission add multi_phase char(1) not null default 'N'";
$sqls[] = "alter table xxt_mission add state tinyint not null default 1 after modify_at";
$sqls[] = "alter table xxt_mission_phase add state tinyint not null default 1 after title";
$sqls[] = "alter table xxt_mission_matter add phase_id varchar(13) not null after mission_id";
$sqls[] = "alter table xxt_enroll add mission_phase_id varchar(13) not null default '' after mission_id";
$sqls[] = "alter table xxt_signin add mission_phase_id varchar(13) not null default '' after mission_id";
$sqls[] = "alter table xxt_group add mission_phase_id varchar(13) not null default '' after mission_id";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;