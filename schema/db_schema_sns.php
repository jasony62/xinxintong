<?php
require_once '../db.php';
/**
 * 渠道——微信公众号
 */
$sql = "create table if not exists xxt_site_wx(";
$sql .= 'id int not null auto_increment';
$sql .= ',siteid varchar(32) not null';
$sql .= ',creater varchar(40) not null';
$sql .= ",create_at int not null";
$sql .= ",by_platform char(1) not null default 'N'"; //使用平台的微信公众号
$sql .= ',qrcode text'; // qrcode image.
$sql .= ",public_id varchar(20) not null default ''"; //微信号
$sql .= ",token varchar(40) not null default ''";
$sql .= ",appid varchar(255) not null default ''";
$sql .= ",appsecret varchar(255) not null default ''";
$sql .= ",cardname varchar(50) not null default ''";
$sql .= ",cardid varchar(36) not null default ''";
$sql .= ",mchid varchar(32) not null default ''";
$sql .= ",joined char(1) not null default 'N'";
$sql .= ",access_token text";
$sql .= ",access_token_expire_at int not null default 0";
$sql .= ',jsapi_ticket text';
$sql .= ',jsapi_ticket_expire_at int not null default 0';
$sql .= ",can_menu char(1) not null default 'N'"; //微信自定义菜单
$sql .= ",can_group_push char(1) not null default 'N'"; //微信群发消息
$sql .= ",can_custom_push char(1) not null default 'N'"; //微信客服消息
$sql .= ",can_fans char(1) not null default 'N'"; //粉丝管理
$sql .= ",can_fansgroup char(1) not null default 'N'"; //微信分组管理
$sql .= ",can_qrcode char(1) not null default 'N'"; //微信场景二维码
$sql .= ",can_oauth char(1) not null default 'N'"; //微信认证
$sql .= ",can_pay char(1) not null default 'N'"; //微信支付
$sql .= ',follow_page_id int not null default 0'; // 引导关注页
$sql .= ",follow_page_name varchar(13) not null default ''"; // 引导关注页
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_site_wx): ' . $mysqli->error;
}
/**
 * 微信公众号粉丝
 */
$sql = "create table if not exists xxt_site_wxfan(";
$sql .= 'id int not null auto_increment';
$sql .= ',siteid varchar(32) not null';
$sql .= ',openid varchar(255) not null';
$sql .= ',groupid int default 0'; // 缺省属于未分组
$sql .= ',subscribe_at int not null';
$sql .= ',unsubscribe_at int not null default 0';
$sql .= ',sync_at int not null'; // 与公众号最后一次同步时间
$sql .= ",headimgurl varchar(255) not null default ''";
$sql .= ",nickname varchar(255) not null default ''";
$sql .= ',sex tinyint not null default 0';
$sql .= ",city varchar(255) not null default ''";
$sql .= ",province varchar(255) not null default ''";
$sql .= ",country varchar(255) not null default ''";
$sql .= ",forbidden char(1) not null default 'N'";
$sql .= ",userid varchar(40) not null default ''"; // 对应的站点用户帐号
$sql .= ",primary key(id)";
$sql .= ")ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_site_wxfan): ' . $mysqli->error;
}
$sql = "ALTER TABLE `xxt_site_wxfan` ADD UNIQUE fanpk( `siteid`, `openid`)";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_site_wxfan): ' . $mysqli->error;
}
/**
 * 微信公众号粉丝分组
 */
$sql = "create table if not exists xxt_site_wxfangroup(";
$sql .= 'id int not null';
$sql .= ',siteid varchar(32) not null';
$sql .= ',name varchar(30) not null';
$sql .= ",primary key(id,siteid)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_site_wxfangroup): ' . $mysqli->error;
}
/**
 * 渠道——易信公众号
 */
$sql = "create table if not exists xxt_site_yx(";
$sql .= 'id int not null auto_increment';
$sql .= ',siteid varchar(32) not null';
$sql .= ',creater varchar(40) not null';
$sql .= ',create_at int not null';
$sql .= ",by_platform char(1) not null default 'N'"; //使用平台的易信公众号
$sql .= ',qrcode text'; // qrcode image.
$sql .= ",public_id varchar(20) not null default ''"; //易信号
$sql .= ",token varchar(40) not null default ''";
$sql .= ",appid varchar(255) not null default ''";
$sql .= ",appsecret varchar(255) not null default ''";
$sql .= ",cardname varchar(50) not null default ''";
$sql .= ",cardid varchar(255) not null default ''";
$sql .= ",joined char(1) not null default 'N'";
$sql .= ',access_token text';
$sql .= ",access_token_expire_at int not null default 0";
$sql .= ",can_menu char(1) not null default 'N'"; //易信自定义菜单
$sql .= ",can_group_push char(1) not null default 'N'"; //易信群发消息
$sql .= ",can_custom_push char(1) not null default 'N'"; //易信客服消息
$sql .= ",can_fans char(1) not null default 'N'"; //粉丝管理
$sql .= ",can_fansgroup char(1) not null default 'N'"; //易信分组管理
$sql .= ",can_qrcode char(1) not null default 'N'"; //易信场景二维码
$sql .= ",can_oauth char(1) not null default 'N'"; //易信认证
$sql .= ",can_p2p char(1) not null default 'N'"; //易信认证接口点对点消息
$sql .= ",can_checkmobile char(1) not null default 'N'"; // 检查手机号是否为易信注册用户
$sql .= ',follow_page_id int not null default 0'; // 引导关注页
$sql .= ",follow_page_name varchar(13) not null default ''"; // 引导关注页
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_site_yx): ' . $mysqli->error;
}
/**
 * 易信公众号粉丝
 */
$sql = "create table if not exists xxt_site_yxfan(";
$sql .= 'id int not null auto_increment';
$sql .= ',siteid varchar(32) not null';
$sql .= ',openid varchar(255) not null';
$sql .= ',groupid int default 0'; // 缺省属于未分组
$sql .= ',subscribe_at int not null';
$sql .= ',unsubscribe_at int not null default 0';
$sql .= ',sync_at int not null'; // 与公众号最后一次同步时间
$sql .= ",headimgurl varchar(255) not null default ''";
$sql .= ",nickname varchar(255) not null default ''";
$sql .= ',sex tinyint not null default 0';
$sql .= ",city varchar(255) not null default ''";
$sql .= ",province varchar(255) not null default ''";
$sql .= ",country varchar(255) not null default ''";
$sql .= ",forbidden char(1) not null default 'N'";
$sql .= ",userid varchar(40) not null default ''"; // 对应的站点用户帐号
$sql .= ",primary key(id)";
$sql .= ")ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_site_yxfan): ' . $mysqli->error;
}
$sql = "ALTER TABLE `xxt_site_yxfan` ADD UNIQUE fanpk(`siteid`, `openid`)";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_site_yxfan): ' . $mysqli->error;
}
/**
 * 易信公众号粉丝分组
 */
$sql = "create table if not exists xxt_site_yxfangroup(";
$sql .= 'id int not null';
$sql .= ',siteid varchar(32) not null';
$sql .= ',name varchar(30) not null';
$sql .= ",primary key(id,siteid)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_site_yxfan): ' . $mysqli->error;
}
/**
 * 渠道——企业号
 */
$sql = "create table if not exists xxt_site_qy(";
$sql .= 'id int not null auto_increment';
$sql .= ',siteid varchar(32) not null';
$sql .= ',creater varchar(40) not null';
$sql .= ',create_at int not null';
$sql .= ',qrcode text'; // qrcode image.
$sql .= ",public_id varchar(20) not null default ''"; //微信号
$sql .= ",token varchar(40) not null default ''";
$sql .= ",corpid varchar(255) not null default ''";
$sql .= ",secret varchar(255) not null default ''";
$sql .= ",encodingaeskey varchar(43) not null default ''";
$sql .= ",agentid int not null default 0";
$sql .= ",joined char(1) not null default 'N'";
$sql .= ",access_token text";
$sql .= ",access_token_expire_at int not null default 0";
$sql .= ',jsapi_ticket text';
$sql .= ',jsapi_ticket_expire_at int not null default 0';
$sql .= ",can_updateab char(1) not null default 'N'"; //更新通讯录
$sql .= ',follow_page_id int not null default 0'; // 引导关注页
$sql .= ",follow_page_name varchar(13) not null default ''"; // 引导关注页
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_site_qy): ' . $mysqli->error;
}
/****************************************************/
/**
 * 渠道——微信公众号
 */
$sql = "create table if not exists xxt_pl_wx(";
$sql .= 'id int not null auto_increment';
$sql .= ',creater varchar(40) not null';
$sql .= ',create_at int not null';
$sql .= ',qrcode text'; // qrcode image.
$sql .= ",public_id varchar(20) not null default ''"; //微信号
$sql .= ",token varchar(40) not null default ''";
$sql .= ",appid varchar(255) not null default ''";
$sql .= ",appsecret varchar(255) not null default ''";
$sql .= ",cardname varchar(50) not null default ''";
$sql .= ",cardid varchar(36) not null default ''";
$sql .= ",mchid varchar(32) not null default ''";
$sql .= ",joined char(1) not null default 'N'";
$sql .= ",access_token text";
$sql .= ",access_token_expire_at int not null default 0";
$sql .= ',jsapi_ticket text';
$sql .= ',jsapi_ticket_expire_at int not null default 0';
$sql .= ",can_menu char(1) not null default 'N'"; //微信自定义菜单
$sql .= ",can_group_push char(1) not null default 'N'"; //微信群发消息
$sql .= ",can_custom_push char(1) not null default 'N'"; //微信客服消息
$sql .= ",can_fans char(1) not null default 'N'"; //粉丝管理
$sql .= ",can_fansgroup char(1) not null default 'N'"; //微信分组管理
$sql .= ",can_qrcode char(1) not null default 'N'"; //微信场景二维码
$sql .= ",can_oauth char(1) not null default 'N'"; //微信认证
$sql .= ",can_pay char(1) not null default 'N'"; //微信支付
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_pl_wx): ' . $mysqli->error;
}
/**
 * 微信公众号粉丝
 */
$sql = "create table if not exists xxt_pl_wxfan(";
$sql .= 'id int not null auto_increment';
$sql .= ',openid varchar(255) not null';
$sql .= ',groupid int default 0'; // 缺省属于未分组
$sql .= ',subscribe_at int not null';
$sql .= ',unsubscribe_at int not null default 0';
$sql .= ',sync_at int not null'; // 与公众号最后一次同步时间
$sql .= ",headimgurl varchar(255) not null default ''";
$sql .= ",nickname varchar(255) not null default ''";
$sql .= ',sex tinyint not null default 0';
$sql .= ",city varchar(255) not null default ''";
$sql .= ",province varchar(255) not null default ''";
$sql .= ",country varchar(255) not null default ''";
$sql .= ",forbidden char(1) not null default 'N'";
$sql .= ",userid varchar(40) not null default ''"; // 对应的站点用户帐号
$sql .= ",primary key(id)";
$sql .= ")ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_pl_wxfan): ' . $mysqli->error;
}
$sql = "ALTER TABLE `xxt_pl_wxfan` ADD UNIQUE fanpk(`openid`)";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_pl_wxfan): ' . $mysqli->error;
}
/**
 * 微信公众号粉丝分组
 */
$sql = "create table if not exists xxt_pl_wxfangroup(";
$sql .= 'id int not null';
$sql .= ',name varchar(30) not null';
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_pl_wxfangroup): ' . $mysqli->error;
}
/**
 * 渠道——易信公众号
 */
$sql = "create table if not exists xxt_pl_yx(";
$sql .= 'id int not null auto_increment';
$sql .= ',creater varchar(40) not null';
$sql .= ',create_at int not null';
$sql .= ',qrcode text'; // qrcode image.
$sql .= ",public_id varchar(20) not null default ''"; //易信号
$sql .= ",token varchar(40) not null default ''";
$sql .= ",appid varchar(255) not null default ''";
$sql .= ",appsecret varchar(255) not null default ''";
$sql .= ",cardname varchar(50) not null default ''";
$sql .= ",cardid varchar(255) not null default ''";
$sql .= ",joined char(1) not null default 'N'";
$sql .= ',access_token text';
$sql .= ",access_token_expire_at int not null default 0";
$sql .= ",can_menu char(1) not null default 'N'"; //易信自定义菜单
$sql .= ",can_group_push char(1) not null default 'N'"; //易信群发消息
$sql .= ",can_custom_push char(1) not null default 'N'"; //易信客服消息
$sql .= ",can_fans char(1) not null default 'N'"; //粉丝管理
$sql .= ",can_fansgroup char(1) not null default 'N'"; //易信分组管理
$sql .= ",can_qrcode char(1) not null default 'N'"; //易信场景二维码
$sql .= ",can_oauth char(1) not null default 'N'"; //易信认证
$sql .= ",can_p2p char(1) not null default 'N'"; //易信认证接口点对点消息
$sql .= ",can_checkmobile char(1) not null default 'N'"; // 检查手机号是否为易信注册用户
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_pl_yx): ' . $mysqli->error;
}
/**
 * 易信公众号粉丝
 */
$sql = "create table if not exists xxt_pl_yxfan(";
$sql .= 'id int not null auto_increment';
$sql .= ',openid varchar(255) not null';
$sql .= ',groupid int default 0'; // 缺省属于未分组
$sql .= ',subscribe_at int not null';
$sql .= ',unsubscribe_at int not null default 0';
$sql .= ',sync_at int not null'; // 与公众号最后一次同步时间
$sql .= ",headimgurl varchar(255) not null default ''";
$sql .= ",nickname varchar(255) not null default ''";
$sql .= ',sex tinyint not null default 0';
$sql .= ",city varchar(255) not null default ''";
$sql .= ",province varchar(255) not null default ''";
$sql .= ",country varchar(255) not null default ''";
$sql .= ",forbidden char(1) not null default 'N'";
$sql .= ",userid varchar(40) not null default ''"; // 对应的站点用户帐号
$sql .= ",primary key(id)";
$sql .= ")ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
$sql = "ALTER TABLE `xxt_pl_yxfan` ADD UNIQUE fanpk(`openid`)";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_pl_yxfan): ' . $mysqli->error;
}
/**
 * 易信公众号粉丝分组
 */
$sql = "create table if not exists xxt_pl_yxfangroup(";
$sql .= 'id int not null';
$sql .= ',name varchar(30) not null';
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_pl_yxfangroup): ' . $mysqli->error;
}
/*******************************/
echo 'finish sns.' . PHP_EOL;