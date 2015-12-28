<?php
require_once '../db.php';
/**
 * 公众平台账号信息
 */
$sql = "create table if not exists xxt_mpaccount(";
$sql .= 'id int not null auto_increment';
$sql .= ',mpid varchar(32) not null';
$sql .= ",mpsrc char(2) not null default ''"; // yx,wx,qy
$sql .= ",yx_mpid varchar(255) not null default ''"; // 公众平台上的账号ID，对应消息中touser字段
$sql .= ",wx_mpid varchar(255) not null default ''"; // 公众平台上的账号ID，对应消息中touser字段
$sql .= ",asparent char(1) not null default 'N'";
$sql .= ",parent_mpid varchar(32) not null default ''";
$sql .= ',name varchar(50) not null';
$sql .= ',qrcode text'; // qrcode image.
$sql .= ",public_id varchar(20) not null default ''"; //微信号，易信号
$sql .= ",token varchar(40) not null default ''";
$sql .= ",yx_appid varchar(255) not null default ''";
$sql .= ",yx_appsecret varchar(255) not null default ''";
$sql .= ",yx_cardname varchar(50) not null default ''";
$sql .= ",yx_cardid varchar(255) not null default ''";
$sql .= ",yx_joined char(1) not null default 'N'";
$sql .= ',yx_token text';
$sql .= ",yx_token_expire_at int not null default 0";
$sql .= ",wx_appid varchar(255) not null default ''";
$sql .= ",wx_appsecret varchar(255) not null default ''";
$sql .= ",wx_cardname varchar(50) not null default ''";
$sql .= ",wx_cardid varchar(36) not null default ''";
$sql .= ",wx_mchid varchar(32) not null default ''";
$sql .= ",wx_joined char(1) not null default 'N'";
$sql .= ",wx_token text";
$sql .= ",wx_token_expire_at int not null default 0";
$sql .= ',wx_jsapi_ticket text';
$sql .= ',wx_jsapi_ticket_expire_at int not null default 0';
$sql .= ",qy_corpid varchar(255) not null default ''";
$sql .= ",qy_secret varchar(255) not null default ''";
$sql .= ",qy_encodingaeskey varchar(43) not null default ''";
$sql .= ",qy_agentid int not null default 0";
$sql .= ",qy_joined char(1) not null default 'N'";
$sql .= ",qy_token text";
$sql .= ",qy_token_expire_at int not null default 0";
$sql .= ',creater varchar(40) not null';
$sql .= ',create_at int not null';
$sql .= ',state tinyint not null default 1'; // 1:正常, 0:停用
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_mpaccount): ' . $mysqli->error;
}
/**
 * 公众账号的全局变量设置
 */
$sql = "create table if not exists xxt_mpsetting(";
$sql .= 'mpid varchar(32) not null';
$sql .= ",yx_menu char(1) not null default 'N'"; //易信自定义菜单
$sql .= ",wx_menu char(1) not null default 'N'"; //微信自定义菜单
$sql .= ",yx_group_push char(1) not null default 'N'"; //易信群发消息
$sql .= ",wx_group_push char(1) not null default 'N'"; //微信群发消息
$sql .= ",yx_custom_push char(1) not null default 'N'"; //易信客服消息
$sql .= ",wx_custom_push char(1) not null default 'N'"; //微信客服消息
$sql .= ",yx_fans char(1) not null default 'N'"; //粉丝管理
$sql .= ",wx_fans char(1) not null default 'N'"; //粉丝管理
$sql .= ",yx_fansgroup char(1) not null default 'N'"; //易信分组管理
$sql .= ",wx_fansgroup char(1) not null default 'N'"; //微信分组管理
$sql .= ",yx_qrcode char(1) not null default 'N'"; //易信场景二维码
$sql .= ",wx_qrcode char(1) not null default 'N'"; //微信场景二维码
$sql .= ",yx_oauth char(1) not null default 'N'"; //易信认证
$sql .= ",wx_oauth char(1) not null default 'N'"; //微信认证
$sql .= ",yx_p2p char(1) not null default 'N'"; //易信认证接口点对点消息
$sql .= ",yx_checkmobile char(1) not null default 'N'"; // 检查手机号是否为易信注册用户
$sql .= ",qy_updateab char(1) not null default 'N'"; //更新通讯录
$sql .= ",wx_pay char(1) not null default 'N'"; //微信支付
$sql .= ',body_ele text'; // 全局背景（removed）
$sql .= ',body_css text'; // 全局背景（removed）
$sql .= ',follow_ele text'; // 关注页内容（removed）
$sql .= ',follow_css text'; // 关注页样式（removed）
$sql .= ",follow_pic text"; // 引导关注的二维码
$sql .= ",heading_pic text"; // 缺省头图
$sql .= ',header_page_id int not null default 0'; // 通用页头
$sql .= ',footer_page_id int not null default 0'; // 通用页尾
$sql .= ',follow_page_id int not null default 0'; // 引导关注页
$sql .= ',shift2pc_page_id int not null default 0'; // 引导到PC端完成
$sql .= ",matter_visible_to_creater char(1) not null default 'N'"; //素材仅对创建者和管理员可见
$sql .= ",admin_contact text"; //管理员联系方式
$sql .= ",admin_email varchar(100) default ''"; //管理员邮箱
$sql .= ",admin_email_pwd varchar(50) default ''"; //管理员邮箱
$sql .= ",admin_email_smtp varchar(100) default ''"; //管理员邮箱
$sql .= ",admin_email_port tinyint default 25"; //管理员邮箱
$sql .= ",admin_email_tls char(1) default 'N'"; //管理员邮箱
$sql .= ",can_article_remark char(1) not null default 'N'"; //是否支持关注用户发表文章评论
$sql .= ",can_member char(1) not null default 'N'"; //是否支持用户注册
$sql .= ",can_member_card char(1) not null default 'N'"; //是否支持申请会员卡
$sql .= ",can_member_checkin char(1) not null default 'N'"; //是否支持用户签到
$sql .= ",can_member_credits char(1) not null default 'N'"; //是否支持会员积分
$sql .= ",primary key(mpid)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_mpsetting): ' . $mysqli->error;
}
/**
 * 设置转发接口
 * 用于向后台业务系统转发收到的消息事件
 */
$sql = "create table if not exists xxt_mprelay(";
$sql .= 'id int not null auto_increment';
$sql .= ',mpid varchar(32) not null';
$sql .= ",title varchar(50) not null default ''";
$sql .= ",url text";
$sql .= ',state tinyint not null default 1'; // 1:正常, 0:停用
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_mprelay): ' . $mysqli->error;
}
/**
 * 公众号授权管理员
 */
$sql = "create table if not exists xxt_mpadministrator(";
$sql .= 'mpid varchar(32) not null';
$sql .= ',uid varchar(40) not null';
$sql .= ',creater varchar(40) not null';
$sql .= ',create_at int not null';
$sql .= ",primary key(mpid,uid)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_mppermission): ' . $mysqli->error;
}
/**
 * 公众平台账号合作者的权限设置
 *
 * 支持的权限包括：
 * 配置公众号 mpsetting
 * 权限设置 mpsecurity
 * 素材管理 matter
 * 回复管理 reply
 * 粉丝管理 fans
 * 认证用户管理 member
 * 应用管理 app
 * 统计分析 analyze
 */
$sql = "create table if not exists xxt_mppermission(";
$sql .= 'mpid varchar(32) not null';
$sql .= ',uid varchar(40) not null';
$sql .= ',permission varchar(50) not null';
$sql .= ",create_p char(1) not null default 'N'";
$sql .= ",read_p char(1) not null default 'N'";
$sql .= ",update_p char(1) not null default 'N'";
$sql .= ",delete_p char(1) not null default 'N'";
$sql .= ",primary key(mpid,uid,permission)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_mppermission): ' . $mysqli->error;
}

echo 'finish mpa.' . PHP_EOL;
