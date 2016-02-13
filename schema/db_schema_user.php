<?php
require_once '../db.php';
/**
 * 访客
 */
$sql = 'create table if not exists xxt_visitor(';
$sql .= 'mpid varchar(32) not null';
$sql .= ',vid varchar(32) not null';
$sql .= ',create_at int not null';
$sql .= ',fid varchar(32)';
$sql .= ",primary key(mpid,vid)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 粉丝
 */
$sql = "create table if not exists xxt_fans(";
$sql .= 'fid varchar(32) not null';
$sql .= ',mpid varchar(32) not null';
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
$sql .= ",userid varchar(40) not null default ''"; // 对应的站点用户帐号
$sql .= ",forbidden char(1) not null default 'N'";
$sql .= ",read_num int not null default 0"; // 累积阅读数
$sql .= ",share_friend_num int not null default 0"; // 累积分享给好友数
$sql .= ",share_timeline_num int not null default 0"; // 累积分享朋友圈数
$sql .= ",coin int not null"; // 虚拟货币
$sql .= ",coin_last_at int not null"; // 最近一次增加虚拟货币
$sql .= ",coin_day int not null"; // 虚拟货币日增量
$sql .= ",coin_week int not null"; // 虚拟货币周增量
$sql .= ",coin_month int not null"; // 虚拟货币月增量
$sql .= ",coin_year int not null"; // 虚拟货币年增量
$sql .= ",primary key(mpid,openid)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 粉丝分组
 */
$sql = "create table if not exists xxt_fansgroup(";
$sql .= 'id int not null';
$sql .= ',mpid varchar(32) not null';
$sql .= ',name varchar(30) not null';
$sql .= ",primary key(id,mpid)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 用户认证服务接口
 *
 * 支持的认证用户记录信息
 * 昵称，姓名，手机号，邮箱，生日
 * 每项内容的设置
 * 隐藏(0)，必填(1)，唯一(2)，不可更改(3)，需要验证(4)，身份标识(5)
 */
$sql = 'create table if not exists xxt_member_authapi(';
$sql .= 'authid int not null auto_increment';
$sql .= ',mpid varchar(32) not null';
$sql .= ',creater varchar(40) not null';
$sql .= ',create_at int not null';
$sql .= ",type varchar(5) not null"; //inner,cus
$sql .= ",valid char(1) not null default 'Y'";
$sql .= ",used int not null default 0";
$sql .= ",name varchar(50) not null";
$sql .= ",url text"; // 入口地址
$sql .= ",validity int not null default 365"; // 认证有效期，以天为单位，最长一年
$sql .= ",attr_mobile char(6) default '001000'";
$sql .= ",attr_email char(6) default '001000'";
$sql .= ",attr_name char(6) default '000000'";
$sql .= ",attr_password char(6) default '110000'";
$sql .= ",extattr text"; // 扩展属性定义
$sql .= ',entry_statement text';
$sql .= ',acl_statement text';
$sql .= ',notpass_statement text';
$sql .= ',auth_code_id int not null default 0';
$sql .= ',sync_to_qy_at int not null default 0'; // 最近一次向企业号通讯录同步的时间
$sql .= ',sync_from_qy_at int not null default 0'; // 最近一次从企业号通讯录同步的时间
$sql .= ",primary key(authid)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/*
 * 通过认证的用户
 *
 * 支持与企业号用户同步
 * 系统支持多个认证源
 * 可以实现openid和认证用户的绑定（xxt_fans中记录了mid）
 */
$sql = 'create table if not exists xxt_member(';
$sql .= 'mid varchar(32) not null';
$sql .= ',fid varchar(32) not null';
$sql .= ',mpid varchar(32) not null'; // 用户在哪个公众号进行的认证
$sql .= ",openid varchar(255) not null default ''"; //
$sql .= ",nickname varchar(255) not null default ''"; //
$sql .= ",authapi_id int not null"; // id from xxt_member_authapi
$sql .= ",authed_identity varchar(255)"; // 用户唯一性的标识
$sql .= ',create_at int not null';
$sql .= ',sync_at int not null'; // 数据的同步时间
$sql .= ',name varchar(255) not null';
$sql .= ',mobile varchar(20) not null';
$sql .= ',email varchar(50) not null';
$sql .= ',password char(64) not null'; // 认证用户可以设置访问口令
$sql .= ',password_salt char(32) not null';
$sql .= ',weixinid varchar(16) not null';
$sql .= ",mobile_verified char(1) not null default 'Y'";
$sql .= ",email_verified char(1) not null default 'Y'";
$sql .= ",verified char(1) not null default 'N'"; // 用户是否已通过认证
$sql .= ",cardno varchar(16) not null default ''";
$sql .= ",level int not null default 0";
$sql .= ",credits int not null default 0";
$sql .= ",depts text";
$sql .= ",tags text";
$sql .= ",forbidden char(1) not null default 'N'";
$sql .= ",extattr text"; //扩展属性
$sql .= ',primary key(mid)) ENGINE=MyISAM DEFAULT CHARSET=utf8';
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * departments
 */
$sql = "create table if not exists xxt_member_department(";
$sql .= 'mpid varchar(32) not null';
$sql .= ",authapi_id int not null"; // id from xxt_member_authapi
$sql .= ',id int not null auto_increment';
$sql .= ",pid int not null default 0"; // 父节点的名称
$sql .= ",seq int not null default 0"; // 在父节点下的排列顺序
$sql .= ',sync_at int not null'; // 数据的同步时间
$sql .= ",name varchar(20) not null default ''";
$sql .= ",fullpath text";
$sql .= ",extattr text"; //扩展属性
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * tags
 */
$sql = "create table if not exists xxt_member_tag(";
$sql .= 'mpid varchar(32) not null';
$sql .= ",authapi_id int not null"; // id from xxt_member_authapi
$sql .= ',id int not null auto_increment';
$sql .= ',sync_at int not null'; // 数据的同步时间
$sql .= ",name varchar(64) not null default ''";
$sql .= ",type tinyint not null default 0"; // 0:自定义,1:岗位
$sql .= ",extattr text"; //扩展属性
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/*
 * member's card
 */
$sql = 'create table if not exists xxt_member_card(';
$sql .= 'mpid varchar(32) not null';
$sql .= ',title varchar(255) not null';
$sql .= ",board_pic varchar(255)";
$sql .= ",badge_pic varchar(255)";
$sql .= ",title_color varchar(25)";
$sql .= ",cardno_color varchar(25)";
$sql .= ",apply_css text";
$sql .= ",apply_ele text";
$sql .= ",apply_js text";
$sql .= ",show_css text";
$sql .= ",show_ele text";
$sql .= ",show_js text";
$sql .= ',primary key(mpid)) ENGINE=MyISAM DEFAULT CHARSET=utf8';
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 用于保存验证邮箱的验证码
 */
$sql = "create table if not exists xxt_access_token(";
$sql .= 'token varchar(32) not null';
$sql .= ',create_at int not null';
$sql .= ',expired int not null default 600'; // 600s
$sql .= ',data text';
$sql .= ",primary key(token)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
//
echo 'finish user.' . PHP_EOL;
