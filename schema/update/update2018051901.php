<?php
require_once '../../db.php';

$sqls = [];
//
$sql = "create table if not exists xxt_enroll_assoc(";
$sql .= "id bigint not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",aid varchar(40) not null";
$sql .= ",record_id int not null";
$sql .= ",entity_a_id int not null";
$sql .= ',entity_a_type tinyint not null';
$sql .= ",entity_b_id int not null";
$sql .= ',entity_b_type tinyint not null';
$sql .= ",assoc_mode tinyint not null default 0";
$sql .= ",assoc_num int not null default 0";
$sql .= ",public char(1) not null default 'Y'";
$sql .= ",first_assoc_at int not null";
$sql .= ",last_assoc_at int not null";
$sql .= ",assoc_text text null"; // 关联描述
$sql .= ",assoc_reason varchar(255) not null default ''"; // 关联理由
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;
//
$sql = 'create table if not exists xxt_enroll_assoc_log(';
$sql .= 'id bigint not null auto_increment';
$sql .= ',siteid varchar(32) not null';
$sql .= ',aid varchar(40) not null';
$sql .= ',record_id int not null';
$sql .= ',assoc_id bigint not null';
$sql .= ",assoc_text text null";
$sql .= ",assoc_reason varchar(255) not null default ''"; // 关联理由
$sql .= ',userid varchar(40) not null';
$sql .= ',link_at int not null default 0';
$sql .= ',unlink_at int not null default 0';
$sql .= ',undo_log_id bigint not null default 0';
$sql .= ",state tinyint not null default 1";
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