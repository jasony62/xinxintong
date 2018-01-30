<?php
require_once '../../db.php';

$sqls = array();
/**
 * 项目中的推荐内容
 */
$sql = "create table if not exists xxt_enroll_plan_stat(";
$sql .= "siteid varchar(32) not null";
$sql .= ",aid varchar(40) not null";
$sql .= ",task_schema_id int not null default 0";
$sql .= ",action_schema_id int not null default 0";
$sql .= ",create_at int not null";
$sql .= ",id varchar(40) not null";
$sql .= ",title varchar(255) not null";
$sql .= ",v varchar(40) not null";
$sql .= ",l varchar(255) not null";
$sql .= ",c double not null";
$sql .= ") ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;
//
$sqls[] = "ALTER TABLE xxt_plan add rp_config text";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;