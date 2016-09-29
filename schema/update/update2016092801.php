<?php
require_once '../../db.php';

$sqls = array();
//
$sql = "create table if not exists xxt_short_url (";
$sql .= "id int not null auto_increment";
$sql .= ",code char(4) not null";
$sql .= ",state int not null default 1";
$sql .= ",siteid varchar(32) not null";
$sql .= ",target_url text not null";
$sql .= ",creater varchar(40) not null";
$sql .= ",creater_name varchar(255) not null default ''";
$sql .= ",create_at int not null";
$sql .= ",expire_at int not null default 0";
$sql .= ",password varchar(40) not null default ''";
$sql .= ",count_limit int not null default 0"; // 可访问的次数
$sql .= ",count_left int not null default 1"; // 剩余访问的次数
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;
//
$sql = "create table if not exists xxt_short_url_token (";
$sql .= "id int not null auto_increment";
$sql .= ",code char(4) not null";
$sql .= ",state int not null default 1";
$sql .= ",access_token varchar(255) not null";
$sql .= ",create_at int not null";
$sql .= ",expire_at int not null";
$sql .= ",user_agent text";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;