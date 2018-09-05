<?php
require_once '../../db.php';

$sql = "create table if not exists xxt_enroll_record_round(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",aid varchar(40) not null";
$sql .= ",rid varchar(13) not null";
$sql .= ",enroll_key varchar(32) not null";
$sql .= ",userid varchar(40) not null default ''";
$sql .= ",add_at int not null"; // 加入轮次的时间
$sql .= ",add_cause char(1) not null"; // 加入的原因 Create:创建新记录，Revise：修订
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;
//
$sql = "insert into xxt_enroll_record_round(siteid,aid,rid,enroll_key,userid,add_at,add_cause) select siteid,aid,rid,enroll_key,userid,first_enroll_at,'C' from xxt_enroll_record";
$sqls[] = $sql;
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;