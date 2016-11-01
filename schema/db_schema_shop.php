<?php
require_once '../db.php';
/**
 * 素材模板商店
 *
 * 一个素材应该只能分享一次
 */
$sql = 'create table if not exists xxt_shop_matter (';
$sql .= 'id int not null auto_increment';
$sql .= ',creater varchar(40) not null';
$sql .= ",creater_name varchar(255) not null default ''"; //from account or fans
$sql .= ',put_at int not null';
$sql .= ",mpid varchar(32) not null default ''";
$sql .= ",siteid varchar(32) not null";
$sql .= ",matter_id varchar(40) not null";
$sql .= ",matter_type varchar(20) not null";
$sql .= ",scenario varchar(255) not null default ''"; // 登记活动场景
$sql .= ",title varchar(70) not null default ''";
$sql .= ',pic text';
$sql .= ',summary varchar(240) not null';
$sql .= ",visible_scope char(1) not null default 'A'"; //A:all,U:self,S:acl
$sql .= ",push_home char(1) not null default 'N'"; // 是否推送到主页
$sql .= ",weight int not null default 0"; // 权重
$sql .= ",score int not null default 0";
$sql .= ",copied_num int not null default 0";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_contribute): ' . $mysqli->error;
}
/**
 * 素材分享访问控制列表
 */
$sql = "create table if not exists xxt_shop_matter_acl (";
$sql .= "id int not null auto_increment";
$sql .= ",shop_matter_id int not null";
$sql .= ",matter_id varchar(40) not null";
$sql .= ",matter_type varchar(20) not null";
$sql .= ",scenario varchar(255) not null default ''"; // 登记活动场景
$sql .= ",creater varchar(40) not null default ''"; // 分享的创建者
$sql .= ",create_at int not null"; // 分享时间
$sql .= ",receiver varchar(40) not null default ''"; // 合作者
$sql .= ",receiver_label varchar(255) not null default ''";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_mission): ' . $mysqli->error;
}
/**
 * platform's home
 */
$sql = "create table if not exists xxt_platform(";
$sql .= "id int not null auto_increment";
$sql .= ",home_page_id int not null default 0"; // 平台主页
$sql .= ",home_page_name varchar(13) not null default ''"; // 平台主页
$sql .= ",home_carousel text"; // 首页轮播
$sql .= ",template_page_id int not null default 0"; // 平台模版库
$sql .= ",template_page_name varchar(13) not null default ''"; // 平台模版库
$sql .= ",site_page_id int not null default 0"; // 平台站点库
$sql .= ",site_page_name varchar(13) not null default ''"; // 平台站点库
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_site): ' . $mysqli->error;
}
/**
 * 申请发布到主页的站点
 */
$sql = "create table if not exists xxt_home_site (";
$sql .= "id int not null auto_increment";
$sql .= ",creater varchar(40) not null";
$sql .= ",creater_name varchar(255) not null default ''";
$sql .= ",put_at int not null";
$sql .= ",siteid varchar(32) not null";
$sql .= ",title varchar(70) not null default ''";
$sql .= ",pic text";
$sql .= ",summary varchar(240) not null default ''";
$sql .= ",score int not null default 0";
$sql .= ",approved char(1) not null default 'N'"; // 是否批准推送到主页
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_contribute): ' . $mysqli->error;
}
/**
 * 申请发布到主页的素材
 */
$sql = 'create table if not exists xxt_home_matter (';
$sql .= 'id int not null auto_increment';
$sql .= ",creater varchar(40) not null";
$sql .= ",creater_name varchar(255) not null default ''";
$sql .= ',put_at int not null';
$sql .= ",siteid varchar(32) not null";
$sql .= ",matter_id varchar(40) not null";
$sql .= ",matter_type varchar(20) not null";
$sql .= ",scenario varchar(255) not null default ''"; // 登记／分组活动场景
$sql .= ",title varchar(70) not null default ''";
$sql .= ',pic text';
$sql .= ',summary varchar(240) not null';
$sql .= ",score int not null default 0";
$sql .= ",approved char(1) not null default 'N'"; // 是否批准推送到主页
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_contribute): ' . $mysqli->error;
}
echo 'finish shop.' . PHP_EOL;