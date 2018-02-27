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
$sql .= ",create_at int not null";
$sql .= ",put_at int not null default 0";
$sql .= ",matter_id varchar(40) not null default ''";
$sql .= ",matter_type varchar(20) not null default ''";
$sql .= ",scenario varchar(255) not null default ''"; // 登记活动场景
$sql .= ",title varchar(70) not null default ''";
$sql .= ",pic text";
$sql .= ",summary varchar(240) not null default ''";
$sql .= ",pub_version varchar(10) not null default ''"; //发布的版本号
$sql .= ",last_version varchar(10) not null default ''"; //最新的版本号
$sql .= ",visible_scope char(1) not null default 'S'"; //P:platform,S:site
$sql .= ",push_home char(1) not null default 'N'"; // 是否推送到主页，只有在平台范围内公开的模板，才能由平台管理员发布到市场
$sql .= ",weight int not null default 0"; // 权重
$sql .= ",score int not null default 0";
$sql .= ",copied_num int not null default 0";
$sql .= ",favor_num int not null default 0"; //收藏数
$sql .= ",coin int not null default 0"; // 使用时需要的积分
$sql .= ",state tinyint not null default 1";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_template): ' . $mysqli->error;
}
/**
 * 模板（登记活动）
 */
$sql = "create table if not exists xxt_template_enroll (";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null"; // 提供模板的站点
$sql .= ",version varchar(10) not null"; //模板版本号
$sql .= ",modifier varchar(40) not null"; // 版本创建者
$sql .= ",modifier_name varchar(255) not null default ''"; // 版本创建者账号
$sql .= ",create_at int not null";
$sql .= ",template_id int not null";
$sql .= ",scenario_config text"; // 登记活动场景的配置参数
$sql .= ",multi_rounds char(1) not null default 'N'"; // 支持轮次
$sql .= ",enrolled_entry_page varchar(20) not null default ''"; //已填写时进入
$sql .= ",open_lastroll char(1) not null default 'Y'"; // 打开最后一条登记记录，还是编辑新的
$sql .= ",data_schemas longtext"; // 登记项定义
$sql .= ",up_said text"; // 版本更新说明
$sql .= ",pub_status char(1) not null default 'N'"; //发布状态
$sql .= ",state tinyint not null default 1";
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
$sql .= ",matter_id varchar(40) not null default ''";
$sql .= ",matter_type varchar(20) not null default ''";
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
$sql .= ",template_version varchar(10) not null"; //模板版本号
$sql .= ",from_siteid varchar(32) not null"; // 提供模板的站点
$sql .= ",from_site_name varchar(50) not null"; // 提供模板的站点
$sql .= ",matter_id varchar(40) not null default ''";
$sql .= ",matter_type varchar(20) not null default ''";
$sql .= ",scenario varchar(255) not null default ''"; // 登记活动场景
$sql .= ",title varchar(70) not null default ''";
$sql .= ',pic text';
$sql .= ",summary varchar(240) not null default ''";
$sql .= ",share char(1) not null default 'N'"; // 共享
$sql .= ',share_at int not null default 0'; // 获得模板的时间
$sql .= ",favor char(1) not null default 'N'"; // 收藏
$sql .= ',favor_at int not null default 0'; // 收藏模板的时间
$sql .= ",purchase char(1) not null default 'N'"; // 购买
$sql .= ',purchase_at int not null default 0'; // 购买模板的时间
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
$sql .= ",autoup_homepage char(1) not null default 'Y'"; // 是否自动更新主页页面
$sql .= ",home_carousel text"; // 首页轮播
$sql .= ",template_page_id int not null default 0"; // 平台模版库
$sql .= ",template_page_name varchar(13) not null default ''"; // 平台模版库
$sql .= ",autoup_templatepage char(1) not null default 'Y'"; // 是否自动更新主页页面
$sql .= ",site_page_id int not null default 0"; // 平台站点库
$sql .= ",site_page_name varchar(13) not null default ''"; // 平台站点库
$sql .= ",autoup_sitepage char(1) not null default 'Y'"; // 是否自动更新主页页面
$sql .= ",home_nav text"; // 平台首页导航条设置
$sql .= ",is_show_site char(1) not null default 'Y'"; // 是否显示团队库
$sql .= ",is_show_template char(1) not null default 'Y'"; // 是否显示模板库
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_platform): ' . $mysqli->error;
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
	echo 'database error(xxt_home_site): ' . $mysqli->error;
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
$sql .= ",site_name varchar(50) not null";
$sql .= ",matter_id varchar(40) not null";
$sql .= ",matter_type varchar(20) not null";
$sql .= ",scenario varchar(255) not null default ''"; // 登记／分组活动场景
$sql .= ",title varchar(70) not null default ''";
$sql .= ',pic text';
$sql .= ',summary varchar(240) not null';
$sql .= ",score int not null default 0";
$sql .= ",approved char(1) not null default 'N'"; // 是否批准推送到主页
$sql .= ",weight int not null default 0";
$sql .= ",home_group char(1) not null default ''";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_home_matter): ' . $mysqli->error;
}
echo 'finish shop.' . PHP_EOL;