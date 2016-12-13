<?php
require_once "../db.php";
/*
 * logs
 */
$sql = "create table if not exists xxt_coin_log(";
$sql .= "id int not null auto_increment";
$sql .= ",mpid varchar(32) not null default ''";
$sql .= ",siteid varchar(32) not null";
$sql .= ",matter_id varchar(40) not null";
$sql .= ",matter_type varchar(20) not null";
$sql .= ",matter_title varchar(70) not null";
$sql .= ",act varchar(255) not null";
$sql .= ",occur_at int not null";
$sql .= ",payer varchar(255) not null";
$sql .= ",payee varchar(255) not null"; // openid should remove
$sql .= ",userid varchar(255) not null"; // 用户ID
$sql .= ",nickname varchar(255) not null"; // 用户昵称
$sql .= ",delta int not null";
$sql .= ",total int not null";
$sql .= ",last_row char(1) not null default 'Y'";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header("HTTP/1.0 500 Internal Server Error");
	echo "database error: " . $mysqli->error;
}
/*
 * rules
 */
$sql = "create table if not exists xxt_coin_rule(";
$sql .= "id int not null auto_increment";
$sql .= ",mpid varchar(32) not null default ''"; // should remove
$sql .= ",siteid varchar(32) not null";
$sql .= ",act varchar(255) not null";
$sql .= ",objid varchar(255) not null default '*'";
$sql .= ",delta int not null"; // should remove
$sql .= ",matter_type varchar(20) not null"; // 素材类型
$sql .= ",matter_filter varchar(40) not null default '*'"; // *|ID:xxxx|
$sql .= ",actor_delta int not null default 0"; // 进行操作的用户增加的积分
$sql .= ",creator_delta int not null default 0"; // 素材创建者增加的积分
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header("HTTP/1.0 500 Internal Server Error");
	echo "database error: " . $mysqli->error;
}
echo "finish coin." . PHP_EOL;