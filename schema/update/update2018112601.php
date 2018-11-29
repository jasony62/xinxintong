<?php
require_once '../../db.php';

$sqls = [];
//
$sql = "create table if not exists xxt_enroll_vote(";
$sql .= "id bigint not null auto_increment";
$sql .= ",aid varchar(40) not null";
$sql .= ",rid varchar(13) not null";
$sql .= ",siteid varchar(32) not null";
$sql .= ",record_id int not null"; // 填写记录的ID
$sql .= ",data_id int not null"; // 填写记录的ID
$sql .= ",vote_at int not null"; // 收藏填写的时间
$sql .= ",userid varchar(40) not null";
$sql .= ",nickname varchar(255) not null default ''";
$sql .= ",state tinyint not null default 1";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
//
$sqls[] = "ALTER TABLE xxt_enroll_record add vote_schema_num int not null default 0";
$sqls[] = "ALTER TABLE xxt_enroll_record add vote_cowork_num int not null default 0";
$sqls[] = "ALTER TABLE xxt_enroll_record_data add vote_num int not null default 0";
//
$sqls[] = "ALTER TABLE xxt_enroll_user add vote_schema_num int not null default 0 after cowork_read_elapse";
$sqls[] = "ALTER TABLE xxt_enroll_user add last_vote_schema_at int not null default 0 after vote_schema_num";
$sqls[] = "ALTER TABLE xxt_enroll_user add vote_cowork_num int not null default 0 after last_vote_schema_at";
$sqls[] = "ALTER TABLE xxt_enroll_user add last_vote_cowork_at int not null default 0 after vote_cowork_num";
//
$sqls[] = $sql;
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;