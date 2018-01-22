<?php
require_once '../../db.php';

$sqls = array();
/**
 * 项目中的推荐内容
 */
$sql = "create table if not exists xxt_mission_agreed (";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",mission_id int not null";
$sql .= ",matter_id varchar(40) not null";
$sql .= ",matter_type varchar(20)";
$sql .= ",obj_unit char(1) not null"; // R:记录，D:数据
$sql .= ",obj_key varchar(32) not null";
$sql .= ",obj_data_id int not null default 0";
$sql .= ",op_at int not null";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;