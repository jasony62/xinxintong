<?php
require_once '../../db.php';

$sqls = array();
//
$sql = 'create table if not exists xxt_enroll_record_tag(';
$sql .= 'id int not null auto_increment';
$sql .= ",siteid varchar(32) not null default ''";
$sql .= ",aid varchar(40) not null";
$sql .= ",create_at int not null default 0"; //
$sql .= ",creater varchar(40) not null default ''"; // 如果是参与人标签，为userid
$sql .= ',label varchar(255) not null';
$sql .= ',level int not null default 0'; // 标签的层级
$sql .= ",seq int not null default 0"; // 标签的顺序
$sql .= ",use_num int not null default 0"; // 使用次数
$sql .= ",scope char(1) not null default 'U'"; // 使用范围，U：参与人，I：发起人
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;
//
$sqls[] = "alter table xxt_enroll_record add data_tag text after tags";
$sqls[] = "alter table xxt_enroll_record_data add tag text after value";
//
$sqls[] = 'drop table if exists xxt_enroll_record_schema';
$sqls[] = 'drop table if exists xxt_enroll_signin_log';
$sqls[] = 'drop table if exists xxt_enroll_lottery_round';
$sqls[] = 'drop table if exists xxt_enroll_lottery';
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;