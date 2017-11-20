<?php
require_once '../db.php';
/**
 * log raw message.
 */
$sql = "create table if not exists xxt_log(";
$sql .= "id int not null auto_increment";
$sql .= ",mpid varchar(32) not null";
$sql .= ",create_at int(10) not null";
$sql .= ",method varchar(255) not null";
$sql .= ",data text";
$sql .= ",user_agent text";
$sql .= ",referer text";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_log): ' . $mysqli->error;
}
/**
 * 集中记录用户对指定的素材做了哪些动作，以便于进行统计分析
 */
$sql = "create table if not exists xxt_log_user_matter(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",userid varchar(40) not null";
$sql .= ",mpid varchar(32) not null default ''"; // should be removed
$sql .= ",openid varchar(255) not null default ''"; // should be removed
$sql .= ",nickname varchar(255) not null default ''";
$sql .= ",matter_id varchar(40) not null";
$sql .= ",matter_type varchar(20) not null";
$sql .= ",matter_title varchar(70) not null";
$sql .= ",mission_id int not null default 0"; // 素材所属项目
$sql .= ",mission_title varchar(70) not null default ''"; // 素材所属项目
$sql .= ",last_action_at int not null"; // should be removed
$sql .= ",read_num int not null default 0"; // should be removed
$sql .= ",share_friend_num int not null default 0"; // should be removed
$sql .= ",share_timeline_num int not null default 0"; // should be removed
$sql .= ",operation varchar(255) not null";
$sql .= ",operate_at int not null";
$sql .= ",operate_data text";
$sql .= ",matter_last_op char(1) not null default 'Y'"; // 是否为素材进行的最后一次指定类型的操作
$sql .= ",matter_op_num int not null default 1"; // 素材进行指定类型的累积次数
$sql .= ",user_last_op char(1) not null default 'Y'"; // 是否为用户对素材进行的最后一次指定类型的操作，用于检查用户对哪些素材进行过指定操作
$sql .= ",user_op_num int not null default 1"; // 用户对素材进行指定操作的累积次数
$sql .= ",user_agent text";
$sql .= ",client_ip varchar(40) not null default ''";
$sql .= ",referer text";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 公众号汇总日志
 * 日增量和累积
 */
$sql = "create table if not exists xxt_log_mpa(";
$sql .= "id int not null auto_increment";
$sql .= ",mpid varchar(32) not null";
$sql .= ",year int not null default 0";
$sql .= ",month int not null default 0";
$sql .= ",day int not null default 0";
$sql .= ",read_inc int not null default 0";
$sql .= ",read_sum int not null default 0";
$sql .= ",sf_inc int not null default 0";
$sql .= ",sf_sum int not null default 0";
$sql .= ",st_inc int not null default 0";
$sql .= ",st_sum int not null default 0";
$sql .= ",fans_inc int not null default 0";
$sql .= ",fans_sum int not null default 0";
$sql .= ",member_inc int not null default 0";
$sql .= ",member_sum int not null default 0";
$sql .= ",islast char(1) not null default 'N'";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_log): ' . $mysqli->error;
}
/**
 * log received parsed msessages.
 * 这应该是一个全局日志，所有收到的用户消息都要记录
 */
$sql = "create table if not exists xxt_log_mpreceive(";
$sql .= "id int not null auto_increment";
$sql .= ",mpid varchar(32) not null";
$sql .= ",msgid bigint(64) not null default 0";
$sql .= ",to_user varchar(255) not null";
$sql .= ",openid varchar(255) not null";
$sql .= ",nickname varchar(255) not null default ''";
$sql .= ",create_at int(10) not null";
$sql .= ",type varchar(10) not null"; // text,image,location,event
$sql .= ",data varchar(255) not null";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_log_mpreceive): ' . $mysqli->error;
}
/**
 * log send parsed msessages.
 * 这应该是一个全局日志，所有发给用户的消息都要记录
 */
$sql = "create table if not exists xxt_log_mpsend(";
$sql .= "id int not null auto_increment";
$sql .= ",mpid varchar(32) not null";
$sql .= ",openid varchar(255) not null";
$sql .= ",groupid varchar(255) not null";
$sql .= ",creater varchar(40) not null";
$sql .= ",create_at int(10) not null";
$sql .= ",content text";
$sql .= ",matter_type varchar(20)";
$sql .= ",matter_id varchar(40)";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_log_mpsend): ' . $mysqli->error;
}
/**
 * 记录图文信息打开的情况
 */
$sql = "create table if not exists xxt_log_matter_read(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",userid varchar(40) not null";
$sql .= ",vid varchar(32) not null default ''";
$sql .= ",openid varchar(255) not null default ''";
$sql .= ",nickname varchar(255) not null default ''";
$sql .= ",read_at int not null";
$sql .= ",mpid varchar(32) not null default ''";
$sql .= ",matter_id varchar(40) not null";
$sql .= ",matter_type varchar(20) not null"; //article,link
$sql .= ",matter_title varchar(70) not null";
$sql .= ",matter_shareby varchar(45)"; // 分享动作ID
$sql .= ",user_agent text";
$sql .= ",client_ip varchar(40) not null default ''";
$sql .= ",search text";
$sql .= ",referer text";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_log_matter_read): ' . $mysqli->error;
}
/**
 * 分享动作日志
 * 记录谁分享了什么，只能记录在打开页面中的分享行为
 */
$sql = "create table if not exists xxt_log_matter_share(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",userid varchar(40) not null";
$sql .= ",shareid varchar(45)"; // 分享行为的主键
$sql .= ",vid varchar(32) not null"; // 谁做的分享
$sql .= ",openid varchar(40) not null";
$sql .= ",nickname varchar(255) not null";
$sql .= ",share_at int not null";
$sql .= ",share_to char(1)"; //朋友圈(T)或好友(F)
$sql .= ",mpid varchar(32) not null default ''";
$sql .= ",matter_id varchar(40) not null";
$sql .= ",matter_type varchar(20) not null";
$sql .= ",matter_title varchar(70) not null";
$sql .= ",matter_shareby varchar(45)"; // 素材是通过谁分享获得的
$sql .= ",user_agent text";
$sql .= ",client_ip varchar(40) not null default ''";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_log_matter_share): ' . $mysqli->error;
}
/**
 * 记录群发消息发送情况
 */
$sql = "create table if not exists xxt_log_massmsg(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",mpid varchar(32) not null";
$sql .= ",matter_id varchar(40) not null";
$sql .= ",matter_type varchar(20) not null";
$sql .= ",sender varchar(40) not null";
$sql .= ",send_at int not null";
$sql .= ",message text"; // 发送的消息内容
$sql .= ",result varchar(255)"; // 发送的消息内容
$sql .= ",msgid bigint(64) not null";
$sql .= ",status varchar(255) not null";
$sql .= ",total_count int not null default 0";
$sql .= ",filter_count int not null default 0";
$sql .= ",sent_count int not null default 0";
$sql .= ",error_count int not null default 0";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_log_tmplmsg): ' . $mysqli->error;
}
/**
 * 记录模板消息发送批次
 */
$sql = "create table if not exists xxt_log_tmplmsg_batch(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null"; // 哪个站点发起的
$sql .= ",event_name varchar(255) not null default ''"; // 事件名称
$sql .= ",send_from varchar(255) not null default ''"; // 记录发起的源头
$sql .= ",tmplmsg_id int not null"; // 系统模板ID
$sql .= ",template_id varchar(255) not null"; // 微信模板ID
$sql .= ",params text"; // 发送的消息的参数
$sql .= ",user_num int not null"; // 送的用户数量
$sql .= ",success_user_num int not null default 0"; // 送成功的用户数量
$sql .= ",remark text"; // 发送内容说明
$sql .= ",creater varchar(40) not null";
$sql .= ",creater_src varchar(10) not null"; // 发送者来源 pl(account)|site(site_account)
$sql .= ",creater_name varchar(255) not null";
$sql .= ",create_at int not null";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_log_tmplmsg): ' . $mysqli->error;
}
/**
 * 记录模板消息接收人
 */
$sql = "create table if not exists xxt_log_tmplmsg_detail(";
$sql .= "id bigint not null auto_increment";
$sql .= ",batch_id int not null"; // 消息批次id
$sql .= ",assoc_with varchar(255) not null default ''"; // 对应的发送对象标志，例如：一条登记记录
$sql .= ",siteid varchar(32) not null"; // 通过哪个站点发送
$sql .= ",tmplmsg_id int not null"; // 系统模板ID
$sql .= ",msgid varchar(50) not null default ''"; // 消息ID
$sql .= ",userid varchar(40) not null";
$sql .= ",openid varchar(255) not null default ''";
$sql .= ",data text"; // 发送的消息内容
$sql .= ",status varchar(255) not null default ''"; // success|failed:user block|failed:system failed
$sql .= ",close_at int not null default 0"; // 关闭消息事件
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_log_tmplmsg): ' . $mysqli->error;
}
/**
 * 记录模板消息发送批次
 */
$sql = "create table if not exists xxt_log_tmplmsg_plbatch(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null"; // 哪个站点发起的
$sql .= ",event_name varchar(255) not null default ''"; // 事件名称
$sql .= ",send_from varchar(255) not null default ''"; // 记录发起的源头
$sql .= ",tmplmsg_id int not null"; // 系统模板ID
$sql .= ",template_id varchar(255) not null"; // 微信模板ID
$sql .= ",params text"; // 发送的消息的参数
$sql .= ",user_num int not null"; // 送的用户数量
$sql .= ",success_user_num int not null default 0"; // 送成功的用户数量
$sql .= ",remark text"; // 发送内容说明
$sql .= ",create_at int not null";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_log_tmplmsg): ' . $mysqli->error;
}
/**
 * 记录模板消息接收人
 */
$sql = "create table if not exists xxt_log_tmplmsg_pldetail(";
$sql .= "id bigint not null auto_increment";
$sql .= ",batch_id int not null"; // 消息批次id
$sql .= ",siteid varchar(32) not null"; // 通过哪个站点发送
$sql .= ",tmplmsg_id int not null"; // 系统模板ID
$sql .= ",msgid varchar(50) not null default ''"; // 消息ID
$sql .= ",userid varchar(40) not null";
$sql .= ",send_to char(2) not null"; //pl|wx|yx|qy
$sql .= ",openid varchar(255) not null default ''";
$sql .= ",data text"; // 发送的消息内容
$sql .= ",status varchar(255) not null default ''"; // success|failed:user block|failed:system failed
$sql .= ",close_at int not null default 0"; // 关闭消息事件
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_log_tmplmsg): ' . $mysqli->error;
}
/**
 * 记录模板消息发送情况
 */
$sql = "create table if not exists xxt_log_tmplmsg(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",mpid varchar(32) not null default ''";
$sql .= ",tmplmsg_id int not null"; // 模板ID
$sql .= ",template_id varchar(255) not null"; // 模板ID
$sql .= ",msgid varchar(50) not null"; // 消息ID
$sql .= ",openid varchar(255) not null";
$sql .= ",data text"; // 发送的消息内容
$sql .= ",create_at int not null";
$sql .= ",status varchar(255)"; // success|failed:user block|failed:system failed
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_log_tmplmsg): ' . $mysqli->error;
}
/**
 * 集中记录用户做了哪些动作，以便于进行统计分析
 *
 * 阅读：xxt_log_matter_read
 * 分享：xxt_log_matter_share
 */
$sql = "create table if not exists xxt_log_user_action(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",userid varchar(40) not null";
$sql .= ",mpid varchar(32) not null default ''"; // should be removed
$sql .= ",vid varchar(32) not null default ''"; // should be removed
$sql .= ",openid varchar(255) not null default ''"; // should be removed
$sql .= ",nickname varchar(255) not null default ''";
$sql .= ",action_at int not null";
$sql .= ",act_read tinyint not null default 0";
$sql .= ",act_share_friend tinyint not null default 0";
$sql .= ",act_share_timeline tinyint not null default 0";
$sql .= ",original_logid int not null";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}

/**
 * 集中记录围绕素材产生的日志，以便于进行统计分析
 */
$sql = "create table if not exists xxt_log_matter_action(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",userid varchar(40) not null default ''";
$sql .= ",mpid varchar(32) not null default ''";
$sql .= ",matter_id varchar(40) not null";
$sql .= ",matter_type varchar(20) not null";
$sql .= ",matter_title varchar(70) not null";
$sql .= ",action_at int not null";
$sql .= ",act_read tinyint not null default 0";
$sql .= ",act_share_friend tinyint not null default 0";
$sql .= ",act_share_timeline tinyint not null default 0";
$sql .= ",original_logid int not null";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 定时任务执行日志
 */
$sql = "create table if not exists xxt_log_timer(";
$sql .= "id int not null auto_increment";
$sql .= ",mpid varchar(32) not null default ''";
$sql .= ",siteid varchar(32) not null default ''";
$sql .= ",task_id int not null";
$sql .= ",occur_at int not null";
$sql .= ",result text";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 后台素材操作日志
 */
$sql = "create table if not exists xxt_log_matter_op(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",operator varchar(40) not null"; //accountid/fid
$sql .= ",operator_name varchar(255) not null"; //from account or fans
$sql .= ",operator_src char(1) not null default 'A'"; //A:accouont|F:fans|M:member
$sql .= ",operation varchar(255) not null"; //Create|Update|Delete
$sql .= ",operate_at int not null";
$sql .= ",matter_id varchar(40) not null";
$sql .= ",matter_type varchar(20) not null";
$sql .= ",matter_title varchar(70) not null default ''";
$sql .= ",matter_summary varchar(240) not null default ''";
$sql .= ",matter_pic text";
$sql .= ",matter_scenario varchar(255) not null default ''"; // 应用场景
$sql .= ",last_op char(1) not null default 'Y'"; // 对象的最后一个操作
$sql .= ",user_last_op char(1) not null default 'N'"; // 用户的最后一个操作
$sql .= ",data text"; // 操作相关数据
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_log_matter_op): ' . $mysqli->error;
}

echo 'finish log.' . PHP_EOL;