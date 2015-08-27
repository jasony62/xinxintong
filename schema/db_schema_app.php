<?php
require_once '../db.php';
/**
 * 投稿应用
 */
$sql = 'create table if not exists xxt_contribute (';
$sql .= 'id varchar(40) not null';
$sql .= ',mpid varchar(32) not null';
$sql .= ',creater varchar(40) not null';
$sql .= ',create_at int not null';
$sql .= ",public_visible char(1) not null default 'N'";
$sql .= ",shift2pc char(1) not null default 'N'";
$sql .= ",can_taskcode char(1) not null default 'N'";
$sql .= ',state tinyint not null default 1';
$sql .= ',title varchar(70) not null';
$sql .= ',pic text';
$sql .= ",summary varchar(240) not null default ''";
$sql .= ',params text'; // 投稿频道{channels:[channel_id]}
$sql .= ",fans_only char(1) not null default 'N'"; // 仅限关注用户打开
$sql .= ",access_control char(1) not null default 'N'";
$sql .= ",authapis text";
$sql .= ",pic_store_at char(1) not null default 'U'"; // 图片存储位置，公众号（M）|用户（U）
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_contribute): ' . $mysqli->error;
}
/**
 * 登记信息通知接收人
 */
$sql = "create table if not exists xxt_contribute_user(";
$sql .= 'id int not null auto_increment';
$sql .= ',mpid varchar(32) not null';
$sql .= ',cid varchar(40) not null'; // contribute's id
$sql .= ',role char(1) not null'; // Initiator|Reviewer|Typesetter
$sql .= ',identity varchar(100) not null';
$sql .= ",idsrc char(2) not null default ''";
$sql .= ",label varchar(255) not null default ''";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}

echo 'finish xxt_contribute.' . PHP_EOL;
