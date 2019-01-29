<?php
require_once '../../db.php';

$sqls = [];
//
$sql = "create table if not exists xxt_enroll_task(";
$sql .= "id int not null auto_increment";
$sql .= ",state tinyint not null default 1";
$sql .= ",aid varchar(40) not null";
$sql .= ",siteid varchar(32) not null default ''";
$sql .= ",rid varchar(13) not null default ''";
$sql .= ",config_type varchar(8) not null default ''"; // vote,score,question,answer
$sql .= ",config_id varchar(13) not null default ''"; // vote_config,score_config,question_config,answer_config
$sql .= ",start_at int not null"; // 轮次开始时间
$sql .= ",end_at int not null"; // 轮次结束时间
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
//
$sqls[] = $sql;
//
$sqls[] = "ALTER TABLE xxt_enroll_topic add task_id int not null default 0";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;