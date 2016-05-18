<?php
require_once "../db.php";
/**
 * site
 */
$sql = "create table if not exists xxt_site(";
$sql .= "id varchar(32) not null";
$sql .= ",name varchar(50) not null";
$sql .= ",heading_pic text"; // 缺省头图
$sql .= ",creater varchar(40) not null";
$sql .= ",creater_name varchar(255) not null default ''";
$sql .= ",create_at int not null";
$sql .= ",asparent char(1) not null default 'N'"; // 是否作为父站点
$sql .= ",site_id varchar(32) not null default ''"; // 父站点ID
$sql .= ",state tinyint not null default 1"; // 1:正常, 0:停用
$sql .= ",home_page_id int not null default 0"; // 站点主页
$sql .= ",home_page_name varchar(13) not null default ''"; // 站点主页
$sql .= ",header_page_id int not null default 0"; // 通用页头
$sql .= ",header_page_name int not null default ''"; // 通用页头
$sql .= ",footer_page_id int not null default 0"; // 通用页尾
$sql .= ",footer_page_name int not null default ''"; // 通用页尾
$sql .= ",shift2pc_page_id int not null default 0"; // 引导到PC端完成
$sql .= ",shift2pc_page_name int not null default ''"; // 引导到PC端完成
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_site): ' . $mysqli->error;
}
/**
 * 站点授权管理员
 */
$sql = "create table if not exists xxt_site_admin(";
$sql .= "siteid varchar(32) not null";
$sql .= ",uid varchar(40) not null";
$sql .= ",creater varchar(40) not null";
$sql .= ",creater_name varchar(255) not null default ''";
$sql .= ",create_at int not null";
$sql .= ",primary key(siteid,uid)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_site_admin): ' . $mysqli->error;
}
/**
 * user account
 */
$sql = "create table if not exists xxt_site_account (";
$sql .= "siteid varchar(32) not null comment '站点id'";
$sql .= ",uid varchar(40) not null comment '用户的id'";
$sql .= ",assoc_id varchar(40) not null default '' comment '用户的关联id'";
$sql .= ",ufrom varchar(20) not null default '' comment '用户来源'";
$sql .= ",uname varchar(50) default null comment '登录用户名'";
$sql .= ",password varchar(64) default null comment '用户密码'";
$sql .= ",salt varchar(32) default null comment '用户附加混淆码'";
$sql .= ",nickname varchar(50) default null comment '用户昵称'";
$sql .= ",headimgurl varchar(255) not null default ''";
$sql .= ",email varchar(255) default null comment 'email'";
$sql .= ",mobile varchar(255) default null comment 'mobile'";
$sql .= ",reg_time int default null comment '注册时间'";
$sql .= ",reg_ip varchar(128) default null comment '注册ip'";
$sql .= ",last_login int default '0' comment '最后登录时间'";
$sql .= ",last_ip varchar(128) default null comment '最后登录 ip'";
$sql .= ",last_active int default null comment '最后活跃时间'";
$sql .= ",forbidden tinyint(3) default '0' comment '是否禁止用户'";
$sql .= ",is_first_login tinyint(1) default '1' comment '首次登录标记'";
$sql .= ",level_id int default null comment '用户级别'";
$sql .= ",read_num int not null default 0"; // 累积阅读数
$sql .= ",share_friend_num int not null default 0"; // 累积分享给好友数
$sql .= ",share_timeline_num int not null default 0"; // 累积分享朋友圈数
$sql .= ",favor_num int not null default 0"; //收藏的数量
$sql .= ",coin int not null"; // 虚拟货币
$sql .= ",coin_last_at int not null"; // 最近一次增加虚拟货币
$sql .= ",coin_day int not null"; // 虚拟货币日增量
$sql .= ",coin_week int not null"; // 虚拟货币周增量
$sql .= ",coin_month int not null"; // 虚拟货币月增量
$sql .= ",coin_year int not null"; // 虚拟货币年增量
$sql .= ",PRIMARY KEY (uid)";
$sql .= ") ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_site_account): ' . $mysqli->error;
}
/**
 * user favor
 */
$sql = "create table if not exists xxt_site_favor(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",userid varchar(32) not null";
$sql .= ",nickname varchar(50)";
$sql .= ",favor_at int not null";
$sql .= ",matter_id varchar(40) not null";
$sql .= ",matter_type varchar(20) not null";
$sql .= ",matter_title varchar(70) not null";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_site_favor): ' . $mysqli->error;
}
/**************************/
/**
 * 自定义用户信息
 *
 * 支持的认证用户记录信息
 * 昵称，姓名，手机号，邮箱，生日
 * 每项内容的设置
 * 隐藏(0)，必填(1)，唯一(2)，不可更改(3)，需要验证(4)，身份标识(5)
 */
$sql = "create table if not exists xxt_site_member_schema(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",title varchar(50) not null";
$sql .= ",creater varchar(40) not null";
$sql .= ",create_at int not null";
$sql .= ",type varchar(5) not null"; //inner,cus
$sql .= ",valid char(1) not null default 'Y'";
$sql .= ",used int not null default 0";
$sql .= ",url text"; // 入口地址
$sql .= ",passed_url text"; // 验证通过后进入的地址
$sql .= ",validity int not null default 365"; // 认证有效期，以天为单位，最长一年
$sql .= ",attr_mobile char(6) default '001000'";
$sql .= ",attr_email char(6) default '001000'";
$sql .= ",attr_name char(6) default '000000'";
$sql .= ",extattr text"; // 扩展属性定义
$sql .= ",code_id int not null default 0";
$sql .= ",page_code_name varchar(13) not null default ''";
$sql .= ",entry_statement text";
$sql .= ",acl_statement text";
$sql .= ",notpass_statement text";
$sql .= ",sync_to_qy_at int not null default 0"; // 最近一次向企业号通讯录同步的时间
$sql .= ",sync_from_qy_at int not null default 0"; // 最近一次从企业号通讯录同步的时间
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/*
 * 通过认证的用户
 *
 * 支持与企业号用户同步
 * 系统支持多个认证源
 */
$sql = "create table if not exists xxt_site_member(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null"; //
$sql .= ",userid varchar(40) not null"; // xxt_site_account
$sql .= ",schema_id int not null"; // id from xxt_site_member_schema
$sql .= ",create_at int not null";
$sql .= ",identity varchar(255) not null default ''"; // 认证用户的唯一标识
$sql .= ",sync_at int not null"; // 数据的同步时间
$sql .= ",name varchar(255) not null";
$sql .= ",mobile varchar(20) not null";
$sql .= ",mobile_verified char(1) not null default 'Y'";
$sql .= ",email varchar(50) not null";
$sql .= ",email_verified char(1) not null default 'Y'";
$sql .= ",extattr text"; // 扩展属性
$sql .= ",depts text"; // 所属部门
$sql .= ",tags text"; // 所属标签
$sql .= ",verified char(1) not null default 'N'"; // 用户是否已通过认证
$sql .= ",forbidden char(1) not null default 'N'";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_site_member): ' . $mysqli->error;
}
$sql = "ALTER TABLE `xxt_site_member` ADD UNIQUE memberpk( `schema_id`, `identity`)";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_site_member): ' . $mysqli->error;
}
/**
 * departments
 */
$sql = "create table if not exists xxt_site_member_department(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",schema_id int not null"; // id from xxt_site_member_schema
$sql .= ",pid int not null default 0"; // 父节点的名称
$sql .= ",seq int not null default 0"; // 在父节点下的排列顺序
$sql .= ",sync_at int not null"; // 数据的同步时间
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
$sql = "create table if not exists xxt_site_member_tag(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",schema_id int not null"; // id from xxt_site_member_schema
$sql .= ",sync_at int not null"; // 数据的同步时间
$sql .= ",name varchar(64) not null default ''";
$sql .= ",type tinyint not null default 0"; // 0:自定义,1:岗位
$sql .= ",extattr text"; //扩展属性
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**********************/
echo 'finish site.' . PHP_EOL;