<?php
require_once '../../db.php';

$sqls = [];
//
$sql = "create table if not exists xxt_template_acl (";
$sql .= "id int not null auto_increment";
$sql .= ",template_id int not null";
$sql .= ",matter_id varchar(40) not null";
$sql .= ",matter_type varchar(20) not null";
$sql .= ",scenario varchar(255) not null default ''"; // 登记活动场景
$sql .= ",creater varchar(40) not null default ''"; // 分享的创建者
$sql .= ",creater_name varchar(255) not null default ''";
$sql .= ",create_at int not null"; // 分享时间
$sql .= ",receiver varchar(40) not null default ''"; // 合作者
$sql .= ",receiver_label varchar(255) not null default ''";
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