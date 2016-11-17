<?php
require_once '../../db.php';

$sqls = [];
//
$sql = 'create table if not exists xxt_site_subscriber (';
$sql .= 'id int not null auto_increment';
$sql .= ",siteid varchar(32) not null"; // 被关注的站点
$sql .= ",site_name varchar(50) not null";
$sql .= ",from_siteid varchar(32) not null"; // 那个站点订阅的
$sql .= ",from_site_name varchar(50) not null";
$sql .= ",creater varchar(40) not null";
$sql .= ",creater_name varchar(255) not null default ''";
$sql .= ',subscribe_at int not null'; // 关注时间
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;
//
$sql = "create table if not exists xxt_site_subscription (";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",put_at int not null"; // 站点获得素材的时间
$sql .= ",from_siteid varchar(32) not null"; // 从哪个站点获得的素材
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