<?php
require_once '../db.php';
/**
 * 素材模板商店（removed）
 *
 * 一个素材应该只能分享一次
 */
// $sql = 'create table if not exists xxt_shop_matter (';
// $sql .= 'id int not null auto_increment';
// $sql .= ',creater varchar(40) not null'; // 生成模板的账号
// $sql .= ",creater_name varchar(255) not null default ''";
// $sql .= ',put_at int not null';
// $sql .= ",mpid varchar(32) not null default ''";
// $sql .= ",siteid varchar(32) not null";
// $sql .= ",matter_id varchar(40) not null";
// $sql .= ",matter_type varchar(20) not null";
// $sql .= ",scenario varchar(255) not null default ''"; // 登记活动场景
// $sql .= ",title varchar(70) not null default ''";
// $sql .= ',pic text';
// $sql .= ',summary varchar(240) not null';
// $sql .= ",visible_scope char(1) not null default 'A'"; //A:all,U:self,S:acl
// $sql .= ",push_home char(1) not null default 'N'"; // 是否推送到主页
// $sql .= ",weight int not null default 0"; // 权重
// $sql .= ",score int not null default 0";
// $sql .= ",copied_num int not null default 0";
// $sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
// if (!$mysqli->query($sql)) {
// 	header('HTTP/1.0 500 Internal Server Error');
// 	echo 'database error(xxt_shop_matter): ' . $mysqli->error;
// }
/**
 * 素材分享访问控制列表（removed）
 */
// $sql = "create table if not exists xxt_shop_matter_acl (";
// $sql .= "id int not null auto_increment";
// $sql .= ",shop_matter_id int not null";
// $sql .= ",matter_id varchar(40) not null";
// $sql .= ",matter_type varchar(20) not null";
// $sql .= ",scenario varchar(255) not null default ''"; // 登记活动场景
// $sql .= ",creater varchar(40) not null default ''"; // 分享的创建者
// $sql .= ",create_at int not null"; // 分享时间
// $sql .= ",receiver varchar(40) not null default ''"; // 合作者
// $sql .= ",receiver_label varchar(255) not null default ''";
// $sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
// if (!$mysqli->query($sql)) {
// 	header('HTTP/1.0 500 Internal Server Error');
// 	echo 'database error(xxt_mission): ' . $mysqli->error;
// }
/**
 * 素材模板
 */
$sql = "create table if not exists xxt_template (";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null"; // 提供模板的站点
$sql .= ",site_name varchar(50) not null"; // 提供模板的站点
$sql .= ",creater varchar(40) not null"; // 生成模板的账号
$sql .= ",creater_name varchar(255) not null default ''";
$sql .= ",put_at int not null";
$sql .= ",matter_id varchar(40) not null";
$sql .= ",matter_type varchar(20) not null";
$sql .= ",scenario varchar(255) not null default ''"; // 登记活动场景
$sql .= ",title varchar(70) not null default ''";
$sql .= ",pic text";
$sql .= ",summary varchar(240) not null";
$sql .= ",visible_scope char(1) not null default 'S'"; //P:platform,S:site
$sql .= ",push_home char(1) not null default 'N'"; // 是否推送到主页，只有在平台范围内公开的模板，才能由平台管理员发布到市场
$sql .= ",weight int not null default 0"; // 权重
$sql .= ",score int not null default 0";
$sql .= ",copied_num int not null default 0";
$sql .= ",coin int not null default 0"; // 使用时需要的积分
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_template): ' . $mysqli->error;
}
/**
 * 指定模版的分享人
 */
$sql = "create table if not exists xxt_template_acl (";
$sql .= "id int not null auto_increment";
$sql .= ",template_id int not null";
$sql .= ",matter_id varchar(40) not null";
$sql .= ",matter_type varchar(20) not null";
$sql .= ",scenario varchar(255) not null default ''"; // 登记活动场景
$sql .= ",creater varchar(40) not null default ''"; // 分享的创建者
$sql .= ",creater_name varchar(255) not null default ''";
$sql .= ",create_at int not null"; // 分享时间
$sql .= ",receiver varchar(40) not null default ''"; // 合作者
$sql .= ",receiver_label varchar(255) not null default ''";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_template): ' . $mysqli->error;
}
/**
 * 素材模板订单
 */
$sql = 'create table if not exists xxt_template_order (';
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null"; // 使用模板的站点
$sql .= ',buyer varchar(40) not null'; // 购买模板的账号
$sql .= ",buyer_name varchar(255) not null default ''";
$sql .= ",template_id int not null"; // 模板ID
$sql .= ",from_siteid varchar(32) not null"; // 提供模板的站点
$sql .= ",from_site_name varchar(50) not null"; // 提供模板的站点
$sql .= ",matter_id varchar(40) not null";
$sql .= ",matter_type varchar(20) not null";
$sql .= ",scenario varchar(255) not null default ''"; // 登记活动场景
$sql .= ",title varchar(70) not null default ''";
$sql .= ',pic text';
$sql .= ',summary varchar(240) not null';
$sql .= ",share char(1) not null default 'N'"; // 共享
$sql .= ',share_at int not null'; // 获得模板的时间
$sql .= ",favor char(1) not null default 'N'"; // 收藏
$sql .= ',favor_at int not null'; // 收藏模板的时间
$sql .= ",purchase char(1) not null default 'N'"; // 购买
$sql .= ',purchase_at int not null'; // 购买模板的时间
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_template_order): ' . $mysqli->error;
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