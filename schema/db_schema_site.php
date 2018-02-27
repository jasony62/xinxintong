<?php
require_once "../db.php";
/**
 * site
 */
$sql = "create table if not exists xxt_site(";
$sql .= "id varchar(32) not null";
$sql .= ",name varchar(50) not null";
$sql .= ",summary varchar(240) not null default ''";
$sql .= ",heading_pic text null"; // 缺省头图
$sql .= ",creater varchar(40) not null";
$sql .= ",creater_name varchar(255) not null default ''";
$sql .= ",create_at int not null";
$sql .= ",asparent char(1) not null default 'N'"; // 是否作为父团队
$sql .= ",site_id varchar(32) not null default ''"; // 父团队ID
$sql .= ",state tinyint not null default 1"; // 1:正常, 0:停用
$sql .= ",home_page_id int not null default 0"; // 团队主页
$sql .= ",home_page_name varchar(13) not null default ''"; // 团队主页
$sql .= ",home_heading_pic text null"; // 首页团队
$sql .= ",autoup_homepage char(1) not null default 'Y'"; // 是否自动更新主页页面
$sql .= ",home_carousel text null"; // 首页轮播
$sql .= ",home_qrcode_group text null"; // 首页群二维码
$sql .= ",header_page_id int not null default 0"; // 通用页头
$sql .= ",header_page_name varchar(13) not null default ''"; // 通用页头
$sql .= ",footer_page_id int not null default 0"; // 通用页尾
$sql .= ",footer_page_name varchar(13) not null default ''"; // 通用页尾
$sql .= ",shift2pc_page_id int not null default 0"; // 引导到PC端完成
$sql .= ",shift2pc_page_name varchar(13) not null default ''"; // 引导到PC端完成
$sql .= ",can_contribute char(1) not null default 'N'"; // 开放投稿
$sql .= ",can_subscribe char(1) not null default 'N'"; // 开放关注
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_site): ' . $mysqli->error;
}
/*
 * 团队配置的通知消息
 */
$sql = "create table if not exists xxt_site_notice(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",creater varchar(40) not null";
$sql .= ",creater_name varchar(255) not null default ''";
$sql .= ",create_at int not null";
$sql .= ",event_name varchar(255) not null"; // 事件名称
$sql .= ",tmplmsg_config_id int not null default 0"; // 对应的模版消息ID，引用xxt_tmplmsg_mapping
$sql .= ",active char(1) not null default 'N'"; //是否已激活
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 团队授权管理员
 */
$sql = "create table if not exists xxt_site_admin(";
$sql .= "siteid varchar(32) not null";
$sql .= ",uid varchar(40) not null"; // 用户的ID
$sql .= ",ulabel varchar(255) not null"; // 用户的标识
$sql .= ",urole char(1) not null default 'A'"; // 合作者角色：Owner，Admin
$sql .= ",creater varchar(40) not null";
$sql .= ",creater_name varchar(255) not null default ''";
$sql .= ",create_at int not null";
$sql .= ",primary key(siteid,uid)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_site_admin): ' . $mysqli->error;
}
/**
 * 团队主页频道
 */
$sql = 'create table if not exists xxt_site_home_channel (';
$sql .= 'id int not null auto_increment';
$sql .= ",creater varchar(40) not null";
$sql .= ",creater_name varchar(255) not null default ''";
$sql .= ',put_at int not null';
$sql .= ",siteid varchar(32) not null";
$sql .= ",channel_id int not null";
$sql .= ",title varchar(70) not null default ''";
$sql .= ",display_name varchar(70) not null default ''";
$sql .= ',pic text null';
$sql .= ',summary varchar(240) not null';
$sql .= ",seq int not null default 0";
$sql .= ",home_group char(1) not null default 'C'";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_site_home_channel): ' . $mysqli->error;
}
/**
 * 关注了团队的站点用户
 */
$sql = 'create table if not exists xxt_site_subscriber (';
$sql .= 'id int not null auto_increment';
$sql .= ",siteid varchar(32) not null"; // 被关注的团队
$sql .= ",site_name varchar(50) not null";
$sql .= ",unionid varchar(40) not null";
$sql .= ",nickname varchar(255) not null default ''";
$sql .= ',subscribe_at int not null'; // 关注时间
$sql .= ',unsubscribe_at int not null default 0'; // 关注时间
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_site_subscriber): ' . $mysqli->error;
}
/**
 * 站点用户通过关注团队获得的素材
 */
$sql = "create table if not exists xxt_site_subscription (";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",site_name varchar(50) not null";
$sql .= ",put_at int not null"; // 团队获得素材的时间
$sql .= ",unionid varchar(40) not null";
$sql .= ",nickname varchar(255) not null default ''";
$sql .= ",matter_id varchar(40) not null";
$sql .= ",matter_type varchar(20)"; //
$sql .= ",matter_title varchar(70) not null";
$sql .= ",matter_pic text null";
$sql .= ",matter_summary varchar(240) not null default ''";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_site_subscription): ' . $mysqli->error;
}
/**
 * 关注了团队的团队
 */
$sql = 'create table if not exists xxt_site_friend (';
$sql .= 'id int not null auto_increment';
$sql .= ",siteid varchar(32) not null"; // 被关注的团队
$sql .= ",site_name varchar(50) not null";
$sql .= ",from_siteid varchar(32) not null"; // 发起关注的团队
$sql .= ",from_site_name varchar(50) not null"; // 发起关注的团队
$sql .= ",creater varchar(40) not null";
$sql .= ",creater_name varchar(255) not null default ''";
$sql .= ',subscribe_at int not null'; // 关注时间
$sql .= ',unsubscribe_at int not null default 0'; // 关注时间
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_site_friend): ' . $mysqli->error;
}
/**
 * 关注了团队的团队获得的订阅内容
 */
$sql = 'create table if not exists xxt_site_friend_subscription (';
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",site_name varchar(50) not null";
$sql .= ",put_at int not null"; // 获得素材的时间
$sql .= ",from_siteid varchar(32) not null"; // 发起关注的团队
$sql .= ",from_site_name varchar(50) not null"; // 发起关注的团队
$sql .= ",matter_id varchar(40) not null";
$sql .= ",matter_type varchar(20)"; //
$sql .= ",matter_title varchar(70) not null";
$sql .= ",matter_pic text null";
$sql .= ",matter_summary varchar(240) not null default ''";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_site_friend_subscription): ' . $mysqli->error;
}
/**
 * 团队访客用户
 */
$sql = "create table if not exists xxt_site_account (";
$sql .= "siteid varchar(32) not null comment '团队id'";
$sql .= ",uid varchar(40) not null comment '用户的id'";
$sql .= ",assoc_id varchar(40) not null default '' comment '用户的关联id'"; // should be removed
$sql .= ",ufrom varchar(20) not null default '' comment '用户来源'";
$sql .= ",uname varchar(50) default null comment '登录用户名'"; // should be removed
$sql .= ",password varchar(64) default null comment '用户密码'"; // should be removed
$sql .= ",salt varchar(32) default null comment '用户附加混淆码'"; // should be removed
$sql .= ",nickname varchar(50) default null comment '用户昵称'";
$sql .= ",headimgurl varchar(255) not null default ''";
$sql .= ",email varchar(255) default null comment 'email'"; // should be removed
$sql .= ",mobile varchar(255) default null comment 'mobile'"; // should be removed
$sql .= ",reg_time int default null comment '注册时间'"; //
$sql .= ",reg_ip varchar(128) default null comment '注册ip'"; //
$sql .= ",last_login int default '0' comment '最后登录时间'"; //
$sql .= ",last_ip varchar(128) default null comment '最后登录 ip'"; //
$sql .= ",last_active int default null comment '最后活跃时间'"; //
$sql .= ",forbidden tinyint(3) default '0' comment '是否禁止用户'"; //
$sql .= ",is_first_login tinyint(1) default '1' comment '首次登录标记'"; // should be removed
$sql .= ",level_id int default null comment '用户级别'";
$sql .= ",read_num int not null default 0"; // 累积阅读数
$sql .= ",share_friend_num int not null default 0"; // 累积分享给好友数
$sql .= ",share_timeline_num int not null default 0"; // 累积分享朋友圈数
$sql .= ",favor_num int not null default 0"; //收藏的数量
$sql .= ",coin int not null default 0"; // 虚拟货币
$sql .= ",coin_last_at int not null default 0"; // 最近一次增加虚拟货币
$sql .= ",coin_day int not null default 0"; // 虚拟货币日增量
$sql .= ",coin_week int not null default 0"; // 虚拟货币周增量
$sql .= ",coin_month int not null default 0"; // 虚拟货币月增量
$sql .= ",coin_year int not null default 0"; // 虚拟货币年增量
$sql .= ",wx_openid varchar(255) not null default ''"; // 绑定的社交账号信息
$sql .= ",is_wx_primary char(1) not null default 'N' comment '是否为团队下第一个和openid绑定的访客账号'";
$sql .= ",yx_openid varchar(255) not null default ''"; // 绑定的社交账号信息
$sql .= ",is_yx_primary char(1) not null default 'N' comment '是否为团队下第一个和openid绑定的访客账号'";
$sql .= ",qy_openid varchar(255) not null default ''"; // 绑定的社交账号信息
$sql .= ",is_qy_primary char(1) not null default 'N' comment '是否为团队下第一个和openid绑定的访客账号'";
$sql .= ",unionid varchar(32) not null default '' comment '用户的注册id'";
$sql .= ",is_reg_primary char(1) not null default 'N' comment '是否为和注册账号绑定的主访客账号，每一个注册账号每一个团队下只有一个主访客账号'";
$sql .= ",PRIMARY KEY (siteid,uid)";
$sql .= ") ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_site_account): ' . $mysqli->error;
}
/**
 * 团队用户收藏记录
 */
$sql = "create table if not exists xxt_site_favor(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",site_name varchar(50) not null";
$sql .= ",unionid varchar(32) not null"; // 注册用户的id
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
/**
 * 团队收藏记录
 */
$sql = "create table if not exists xxt_site_friend_favor(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",creater varchar(40) not null";
$sql .= ",creater_name varchar(255) not null";
$sql .= ",favor_at int not null";
$sql .= ",from_siteid varchar(32) not null";
$sql .= ",from_site_name varchar(50) not null";
$sql .= ",matter_id varchar(40) not null";
$sql .= ",matter_type varchar(20) not null";
$sql .= ",matter_title varchar(70) not null";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_site_friend_favor): ' . $mysqli->error;
}
/**
 * 团队投稿记录
 */
$sql = "create table if not exists xxt_site_contribute(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null"; // 接收投稿的团队
$sql .= ",from_siteid varchar(32) not null"; // 进行投稿的团队
$sql .= ",from_site_name varchar(50) not null";
$sql .= ",creater varchar(40) not null"; // 投稿用户
$sql .= ",creater_name varchar(255) not null"; // 投稿用户
$sql .= ",matter_id varchar(40) not null";
$sql .= ",matter_type varchar(20) not null";
$sql .= ",matter_title varchar(70) not null";
$sql .= ",matter_summary varchar(240) not null default ''";
$sql .= ",matter_pic text null";
$sql .= ",create_at int not null"; // 投稿时间
$sql .= ",browse_at int not null default 0"; // 浏览时间
$sql .= ",close_at int not null default 0"; // 关闭时间
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_site_friend_favor): ' . $mysqli->error;
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
$sql .= ",matter_id varchar(40) not null default ''"; // 所属素材
$sql .= ",matter_type varchar(20) not null default ''"; // 所属素材
$sql .= ",title varchar(50) not null";
$sql .= ",creater varchar(40) not null";
$sql .= ",create_at int not null";
$sql .= ",start_at int not null default 0"; // 开始时间
$sql .= ",end_at int not null default 0"; // 结束时间
$sql .= ",valid char(1) not null default 'Y'";
$sql .= ",url text null"; // 入口地址
$sql .= ",passed_url text null"; // 验证通过后进入的地址
$sql .= ",validity int not null default 365"; // 认证有效期，以天为单位，最长一年
$sql .= ",attr_mobile char(6) default '001000'";
$sql .= ",attr_email char(6) default '001000'";
$sql .= ",attr_name char(6) default '000000'";
$sql .= ",extattr text null"; // 扩展属性定义
$sql .= ",ext_attrs text null"; // 扩展属性定义
$sql .= ",code_id int not null default 0"; // 定制页面
$sql .= ",page_code_name varchar(13) not null default ''"; // 定制页面
$sql .= ",sync_to_qy_at int not null default 0"; // 最近一次向企业号通讯录同步的时间
$sql .= ",sync_from_qy_at int not null default 0"; // 最近一次从企业号通讯录同步的时间
$sql .= ",auto_verified char(1) not null default 'Y'"; // 用户默认是否通过认证
$sql .= ",require_invite char(1) not null default 'N'"; // 是否需要邀请码
$sql .= ",at_user_home char(1) not null default 'N'"; // 是否出现在用户主页
$sql .= ",is_wx_fan char(1) not null default 'N'"; // 是否为微信公众号关注用户
$sql .= ",is_yx_fan char(1) not null default 'N'"; // 是否为易信公众号关注用户
$sql .= ",is_qy_fan char(1) not null default 'N'"; // 是否为微信企业号关注用户
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 验证邀请码
 */
$sql = "create table if not exists xxt_site_member_invite(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",creater varchar(40) not null";
$sql .= ",schema_id int not null";
$sql .= ",create_at int not null"; // 邀请码生产时间
$sql .= ",expire_at int not null"; // 邀请码到期时间
$sql .= ",code varchar(6) not null default ''"; // 邀请码的值
$sql .= ",max_count int not null default 0"; // 可以使用的次数
$sql .= ",use_count int not null default 0"; // 使用的次数
$sql .= ",stop char(1) not null default 'N'"; // 停止使用
$sql .= ",state int not null default 1";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/*
 * 团队通讯录联系人
 *
 * 支持与企业号用户同步
 * 系统支持多个认证源
 */
$sql = "create table if not exists xxt_site_member(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null"; //
$sql .= ",userid varchar(40) not null"; // xxt_site_account
$sql .= ",unionid varchar(32) not null default ''"; // account
$sql .= ",schema_id int not null"; // id from xxt_site_member_schema
$sql .= ",create_at int not null";
$sql .= ",modify_at int not null";
$sql .= ",identity varchar(255) not null default ''"; // 认证用户的唯一标识
$sql .= ",sync_at int not null default 0"; // 数据和企业号的同步时间
$sql .= ",name varchar(255) not null default ''";
$sql .= ",mobile varchar(20) not null default ''";
$sql .= ",mobile_verified char(1) not null default 'Y'";
$sql .= ",email varchar(50) not null default ''";
$sql .= ",email_verified char(1) not null default 'Y'";
$sql .= ",extattr text null"; // 扩展属性
$sql .= ",depts text null"; // 所属部门
$sql .= ",tags text null"; // 所属标签
$sql .= ",verified char(1) not null default 'N'"; // 用户是否已通过认证
$sql .= ",forbidden char(1) not null default 'N'";
$sql .= ",invite_code varchar(6) not null default ''"; // 邀请码
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
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
$sql .= ",fullpath text null";
$sql .= ",extattr text null"; //扩展属性
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
$sql .= ",extattr text null"; //扩展属性
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 站点活跃数表
 */
$sql = "create table if not exists xxt_site_active(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",userid varchar(40) not null";
$sql .= ",nickname varchar(255) not null";
$sql .= ",user_active_sum int not null default 0"; //用户产生的活跃数总数
$sql .= ",operation varchar(255) not null";
$sql .= ",operation_active_sum int not null default 0"; //行为产生的活跃数总数
$sql .= ",year int not null";
$sql .= ",year_active_sum int not null default 0";
$sql .= ",month int not null";
$sql .= ",month_active_sum int not null default 0";
$sql .= ",day int not null";
$sql .= ",day_active_sum int not null default 0";
$sql .= ",operation_at int not null";
$sql .= ",active_last_op char(1) not null default 'Y'";
$sql .= ",user_last_op char(1) not null default 'Y'";
$sql .= ",operation_last_op char(1) not null default 'Y'";
$sql .= ",active_one_num int not null default 0"; //单次增加的活跃数
$sql .= ",active_sum int not null default 0"; //活跃数总数
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 站点素材的列表信息
 * 删除素材的时候删除记录
 */
$sql = "create table if not exists xxt_site_matter(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",mission_id int not null";
$sql .= ",creater varchar(40) not null";
$sql .= ",creater_name varchar(255) not null default ''";
$sql .= ",creater_src char(1)";
$sql .= ",create_at int not null";
$sql .= ",matter_id varchar(40) not null";
$sql .= ",matter_type varchar(20)";
$sql .= ",matter_title varchar(70) not null";
$sql .= ",matter_category varchar(3) not null"; //app/doc
$sql .= ",scenario varchar(255) not null default ''";
$sql .= ",start_at int not null default 0";
$sql .= ",end_at int not null default 0";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**********************/
echo 'finish site.' . PHP_EOL;