<?php
require_once '../../db.php';

$sqls = array();
$sql = "create table if not exists xxt_log_matter_op(";
$sql .= "id int not null auto_increment";
$sql .= ",mpid varchar(32) not null";
$sql .= ",operator varchar(40) not null"; //accountid/fid
$sql .= ",operator_name varchar(255) not null"; //from account or fans
$sql .= ",operator_src char(1) not null default 'A'"; //A:accouont|F:fans|M:member
$sql .= ",operation char(1) not null"; //Create|Update|Delete
$sql .= ",operate_at int not null";
$sql .= ",matter_id varchar(40) not null";
$sql .= ",matter_type varchar(20) not null";
$sql .= ",matter_title varchar(70) not null";
$sql .= ",matter_summary varchar(240) not null";
$sql .= ",matter_pic text";
$sql .= ",last_op char(1) not null default 'Y'";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";

$sqls[] = $sql;

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo "end update " . __FILE__ . PHP_EOL;