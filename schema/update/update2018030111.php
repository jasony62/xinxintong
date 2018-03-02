<?php
require_once '../../db.php';

$sqls = array();
//
$sql = "create table if not exists xxt_site_invoke(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",invoker varchar(40) not null";
$sql .= ",invoker_name varchar(255) not null default ''";
$sql .= ",invoker_ip varchar(255) not null default ''";// 可以多个ip
$sql .= ",create_at int not null";
$sql .= ",secret varchar(32) not null default ''";// 外部系获取token的凭证
$sql .= ",secret_creater varchar(40) not null default ''";
$sql .= ",secret_creater_name varchar(255) not null default ''";
$sql .= ",secret_create_at int not null default 0";
$sql .= ",secret_modify_log text";
$sql .= ",state int not null default 1";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;
//
$sql = "create table if not exists xxt_site_invoke_token(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",invoke_id int not null";
$sql .= ",secret varchar(32) not null default ''";
$sql .= ",access_token varchar(32) not null default ''";
$sql .= ",create_at int not null default 0";
$sql .= ",expire_at int not null default 0";
$sql .= ",user_agent text";
$sql .= ",state int not null default 1";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;
//
$sql = "create table if not exists xxt_site_invoke_log(";
$sql .= "id int not null auto_increment";
$sql .= ",access_token varchar(32) not null default ''";
$sql .= ",create_at int not null default 0";
$sql .= ",user_agent text";
$sql .= ",user_ip varchar(15) not null default ''";
$sql .= ",access_status text";
$sql .= ",state int not null default 1";
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