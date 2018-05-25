<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "ALTER TABLE xxt_enroll_user add do_repos_read_num int not null default 0 after agree_remark_num";
$sqls[] = "ALTER TABLE xxt_enroll_user add do_repos_read_elapse int not null default 0 after do_repos_read_num";
//
$sqls[] = "ALTER TABLE xxt_enroll_user add do_topic_read_num int not null default 0 after do_repos_read_elapse";
$sqls[] = "ALTER TABLE xxt_enroll_user add topic_read_num int not null default 0 after do_topic_read_num";
$sqls[] = "ALTER TABLE xxt_enroll_user add do_topic_read_elapse int not null default 0 after topic_read_num";
$sqls[] = "ALTER TABLE xxt_enroll_user add topic_read_elapse int not null default 0 after do_topic_read_elapse";

$sqls[] = "ALTER TABLE xxt_enroll_user add do_cowork_read_num int not null default 0 after topic_read_elapse";
$sqls[] = "ALTER TABLE xxt_enroll_user add cowork_read_num int not null default 0 after do_cowork_read_num";
$sqls[] = "ALTER TABLE xxt_enroll_user add do_cowork_read_elapse int not null default 0 after cowork_read_num";
$sqls[] = "ALTER TABLE xxt_enroll_user add cowork_read_elapse int not null default 0 after do_cowork_read_elapse";
//
$sql = "create table if not exists xxt_enroll_trace(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",aid varchar(40) not null";
$sql .= ",rid varchar(13) not null default ''"; // 
$sql .= ",page varchar(13) not null default ''"; // 
$sql .= ",record_id int not null default 0"; // 
$sql .= ",topic_id int not null default 0"; // 
$sql .= ",userid varchar(40) not null";
$sql .= ",nickname varchar(255) not null default ''";
$sql .= ",event_first varchar(255) not null default ''";
$sql .= ",event_first_at int not null default 0";
$sql .= ",event_end varchar(255) not null default ''";
$sql .= ",event_end_at int not null default 0";
$sql .= ",event_elapse int not null default 0";// 事件总时长
$sql .= ",events text null"; // 事件
$sql .= ",user_agent text null";
$sql .= ",client_ip varchar(40) not null default ''";
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