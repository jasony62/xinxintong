<?php
require_once '../../db.php';

$sqls = [];
//
$sql = "create table if not exists xxt_site_wxa(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",creater varchar(40) not null";
$sql .= ",create_at int not null";
$sql .= ",title varchar(255) not null default ''"; // 微信公众号名称
$sql .= ",qrcode text null"; // qrcode image.
$sql .= ",appid varchar(255) not null default ''";
$sql .= ",appsecret varchar(255) not null default ''";
$sql .= ",access_token text null";
$sql .= ",access_token_expire_at int not null default 0";
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