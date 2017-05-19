<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "alter table xxt_site_member_schema add require_invite char(1) not null default 'N'";
$sqls[] = "alter table xxt_site_member add invite_code varchar(6) not null default ''";
//
$sql = "create table if not exists xxt_site_member_invite(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",creater varchar(40) not null";
$sql .= ",schema_id int not null";
$sql .= ",create_at int not null"; // 邀请码生产时间
$sql .= ",expire_at int not null"; // 邀请码到期时间
$sql .= ",code varchar(6) not null default ''"; // 邀请码的值
$sql .= ",max_count int not null default 0"; // 可以使用的次数
$sql .= ",use_count int not null default 0"; // 使用的次数
$sql .= ",stop char(1) not null default 'N'"; // 停止使用
$sql .= ",state int not null default 1";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
//
$sqls[] = $sql;

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;