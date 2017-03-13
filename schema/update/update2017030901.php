<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "insert into xxt_site(id,name) values('platform','信信通')";
$sqls[] = "alter table xxt_site_subscriber add userid varchar(40) not null default '' after creater_name";
$sqls[] = "alter table xxt_site_subscriber add nickname varchar(255) not null default '' after userid";
$sqls[] = "alter table xxt_site_subscriber add unsubscribe_at int not null default 0";
$sqls[] = "alter table xxt_site_subscription add site_name varchar(50) not null after siteid";
$sqls[] = "alter table xxt_site_subscription add userid varchar(40) not null default '' after from_site_name";
$sqls[] = "alter table xxt_site_subscription add nickname varchar(255) not null default '' after userid";
//
$sql = 'create table if not exists xxt_site_friend (';
$sql .= 'id int not null auto_increment';
$sql .= ",siteid varchar(32) not null";
$sql .= ",site_name varchar(50) not null";
$sql .= ",from_siteid varchar(32) not null";
$sql .= ",from_site_name varchar(50) not null";
$sql .= ",creater varchar(40) not null";
$sql .= ",creater_name varchar(255) not null default ''";
$sql .= ',subscribe_at int not null';
$sql .= ',unsubscribe_at int not null default 0';
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;
//
$sql = 'create table if not exists xxt_site_friend_subscription (';
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",site_name varchar(50) not null";
$sql .= ",put_at int not null";
$sql .= ",from_siteid varchar(32) not null";
$sql .= ",from_site_name varchar(50) not null";
$sql .= ",matter_id varchar(40) not null";
$sql .= ",matter_type varchar(20)"; //
$sql .= ",matter_title varchar(70) not null";
$sql .= ",matter_pic text";
$sql .= ",matter_summary varchar(240) not null default ''";
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