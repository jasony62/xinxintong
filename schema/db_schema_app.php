<?php
require_once '../db.php';
/**
 * 投稿应用
 */
$sql = "create table if not exists xxt_contribute (";
$sql .= "id varchar(40) not null";
$sql .= ",mpid varchar(32) not null";
$sql .= ",siteid varchar(32) not null";
$sql .= ",creater varchar(40) not null";
$sql .= ",creater_name varchar(255) not null default ''"; //from account or fans
$sql .= ",creater_src char(1)"; //A:accouont|F:fans
$sql .= ",create_at int not null";
$sql .= ",modifier varchar(40) not null default ''"; //accountid/fid
$sql .= ",modifier_name varchar(255) not null default ''"; //from account or fans
$sql .= ",modifier_src char(1)"; //A:accouont|F:fans|M:member
$sql .= ",modify_at int not null";
$sql .= ",public_visible char(1) not null default 'N'";
$sql .= ",shift2pc char(1) not null default 'N'";
$sql .= ",can_taskcode char(1) not null default 'N'";
$sql .= ",state tinyint not null default 1";
$sql .= ",title varchar(70) not null";
$sql .= ",pic text";
$sql .= ",summary varchar(240) not null default ''";
$sql .= ",params text"; // 投稿频道{channels:[channel_id]}
$sql .= ",template_body text"; // 投稿内容模版
$sql .= ",fans_only char(1) not null default 'N'"; // 仅限关注用户打开
$sql .= ",access_control char(1) not null default 'N'";
$sql .= ",initiator_schemas text";
$sql .= ",pic_store_at char(1) not null default 'U'"; // 图片存储位置，公众号（M）|用户（U）
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_contribute): ' . $mysqli->error;
}
/**
 * 投稿用户
 */
$sql = "create table if not exists xxt_contribute_user(";
$sql .= "id int not null auto_increment";
$sql .= ",mpid varchar(32) not null";
$sql .= ",siteid varchar(32) not null";
$sql .= ",cid varchar(40) not null"; // contribute's id
$sql .= ",role char(1) not null"; // Initiator|Reviewer|Typesetter
$sql .= ",identity varchar(100) not null";
$sql .= ",idsrc char(2) not null default ''";
$sql .= ",label varchar(255) not null default ''";
$sql .= ",level int not null default 1";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}

echo 'finish xxt_contribute.' . PHP_EOL;
