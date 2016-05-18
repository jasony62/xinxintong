<?php
require_once '../db.php';
/*
 * 信息墙
 */
$sql = 'create table if not exists xxt_wall(';
$sql .= 'id varchar(32) not null';
$sql .= ',mpid varchar(32) not null';
$sql .= ',creater varchar(40) not null';
$sql .= ",create_at int not null";
$sql .= ",active char(1) not null default 'N'";
$sql .= ",title varchar(70) not null";
$sql .= ',pic text'; // 分享或生成链接时的图片
$sql .= ',summary varchar(240) not null'; // 分享或生成链接时的摘要
$sql .= ",access_control char(1) not null default 'N'";
$sql .= ",authapis text";
$sql .= ",user_url text";
$sql .= ",join_reply text";
$sql .= ",quit_reply text";
$sql .= ",quit_cmd varchar(10) not null default ''";
$sql .= ",skip_approve char(1) not null default 'N'";
$sql .= ",push_others char(1) not null default 'Y'";
$sql .= ",entry_ele text";
$sql .= ",entry_css text";
$sql .= ",body_css text";
$sql .= ',primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8';
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 信息墙的页面
 */
$sql = 'create table if not exists xxt_wall_page(';
$sql .= 'id int not null auto_increment';
$sql .= ',mpid varchar(32) not null';
$sql .= ',wid varchar(32) not null'; //wall
$sql .= ",creater varchar(40) not null default ''";
$sql .= ",create_at int not null";
$sql .= ",type varchar(30) not null"; //op
$sql .= ",name varchar(70) not null default ''";
$sql .= ",title varchar(70) not null default ''";
$sql .= ",summary varchar(240) not null default ''";
$sql .= ",code_id int not null default 0"; // from xxt_code_page
$sql .= ",code_name varchar(13) not null default ''"; // from xxt_code_page
$sql .= ",seq int not null";
$sql .= ',primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8';
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 信息墙用户状态
 */
$sql = 'create table if not exists xxt_wall_enroll(';
$sql .= 'mpid varchar(32) not null';
$sql .= ',wid varchar(32) not null';
$sql .= ",openid varchar(255) not null";
$sql .= ",remark varchar(255) not null default ''";
$sql .= ",join_at int not null"; // 加入时间
$sql .= ",last_msg_at int not null default 0";
$sql .= ",close_at int not null default 0";
$sql .= ',primary key(wid,openid)) ENGINE=MyISAM DEFAULT CHARSET=utf8';
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 用户向信息墙发送的信息
 */
$sql = 'create table if not exists xxt_wall_log(';
$sql .= 'id int not null auto_increment';
$sql .= ',mpid varchar(32) not null';
$sql .= ',wid varchar(32) not null';
$sql .= ",openid varchar(255) not null";
$sql .= ",publish_at int not null";
$sql .= ",data text";
$sql .= ",data_type varchar(5)"; //text|image
$sql .= ",data_media_id varchar(255)";
$sql .= ",approve_at int not null default 0";
$sql .= ",approved tinyint not null default 0"; //0:pending,1:approved,2:reject
$sql .= ',primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8';
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}

echo 'finish wall.' . PHP_EOL;