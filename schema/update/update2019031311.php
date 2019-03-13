<?php
require_once '../../db.php';

$sqls = [];
/**
 * 支持的第三方登录
 */
$sql = "create table if not exists xxt_account_app (";
$sql .= "id int(11) unsigned NOT NULL AUTO_INCREMENT";
$sql .= ",creater varchar(50) NOT NULL";
$sql .= ",create_at int(11) NOT NULL";
$sql .= ",appname varchar(50) NOT NULL default ''"; //第三方名称
$sql .= ",appid varchar(100) NOT NULL default ''"; // 
$sql .= ",appsecret varchar(100) NOT NULL default ''"; // 
$sql .= ",scope varchar(20) NOT NULL default ''"; // 获取身份权限
$sql .= ",oauthurl  varchar(255) NOT NULL default ''"; // 用户授权地址
$sql .= ",state tinyint not null default 1";
$sql .= ",primary key (id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;
/**
 * 第三方登录的用户
 */
$sql = "create table if not exists xxt_account_app_user (";
$sql .= "id int(11) unsigned NOT NULL AUTO_INCREMENT";
$sql .= ",app_id int(11) NOT NULL"; // 
$sql .= ",openid varchar(255) not null";
$sql .= ",reg_time int(11) NOT NULL";
$sql .= ",headimgurl varchar(255) not null default ''";
$sql .= ",nickname varchar(255) not null default ''";
$sql .= ",sex tinyint not null default 0";
$sql .= ",city varchar(255) not null default ''";
$sql .= ",province varchar(255) not null default ''";
$sql .= ",country varchar(255) not null default ''";
$sql .= ",forbidden char(1) not null default 'N'";
$sql .= ",primary key (id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;