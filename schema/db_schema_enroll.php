<?php
require_once '../db.php';
/*********************************************
 *登记活动
 *********************************************/
$sql = "create table if not exists xxt_enroll(";
$sql .= "id varchar(40) not null";
$sql .= ",siteid varchar(32) not null";
$sql .= ",mpid varchar(32) not null";
$sql .= ",creater varchar(40) not null default ''";
$sql .= ",creater_name varchar(255) not null default ''"; //from account or fans
$sql .= ",creater_src char(1)"; //A:accouont|F:fans
$sql .= ",create_at int not null";
$sql .= ",modifier varchar(40) not null default ''"; //accountid/fid
$sql .= ",modifier_name varchar(255) not null default ''"; //from account or fans
$sql .= ",modifier_src char(1)"; //A:accouont|F:fans|M:member
$sql .= ",modify_at int not null";
$sql .= ",public_visible char(1) not null default 'N'";
$sql .= ",shift2pc char(1) not null default 'N'"; //
$sql .= ",can_taskcode char(1) not null default 'N'";
$sql .= ",state tinyint not null default 1"; //0:删除,1:配置,2:运行
$sql .= ",title varchar(255) not null default ''";
$sql .= ",summary varchar(240) not null default ''"; // 分享或生成链接时的摘要
$sql .= ",pic text"; // 分享或生成链接时的图片
$sql .= ",mission_id int not null default 0"; // 所属项目
$sql .= ",mission_phase_id varchar(13) not null default ''"; // 所属项目阶段
$sql .= ",scenario varchar(255) not null default ''"; // 登记活动场景
$sql .= ",scenario_config text"; // 登记活动场景的配置参数
$sql .= ",start_at int not null default 0"; // 开始时间
$sql .= ",before_start_page varchar(20) not null default ''";
$sql .= ",end_at int not null default 0"; // 结束时间
$sql .= ",after_end_page varchar(20) not null default ''";
$sql .= ",access_control char(1) not null default 'N'";
$sql .= ",authapis text";
$sql .= ",entry_rule text"; // 参与规则
$sql .= ",success_matter_type varchar(14)"; // 签到成功回复
$sql .= ",success_matter_id varchar(128)";
$sql .= ",failure_matter_type varchar(14)"; // 签到失败回复
$sql .= ",failure_matter_id varchar(128)";
$sql .= ",enrolled_entry_page varchar(20) not null default ''";
$sql .= ",receiver_page varchar(20) not null default ''";
$sql .= ",remark_notice_page varchar(20) not null default ''";
$sql .= ",form_code_id int not null default 0"; // 表单页 should remove
$sql .= ",lottery_page_id int not null default 0"; // 抽奖页 should remove
$sql .= ",open_lastroll char(1) not null default 'Y'"; // 打开最后一条登记记录，还是编辑新的
$sql .= ",multi_rounds char(1) not null default 'N'"; // 支持轮次
$sql .= ",can_like_record char(1) not null default 'N'"; // 支持对登记记录点赞 should remove
$sql .= ",can_remark_record char(1) not null default 'N'"; // 支持对登记记录评论 should remove
$sql .= ",can_autoenroll char(1) not null default 'N'"; // 是否支持自动登记
$sql .= ",can_invite char(1) not null default 'N'"; // 是否支持邀请
$sql .= ",can_signin char(1) not null default 'N'"; // 是否支持签到
$sql .= ",can_lottery char(1) not null default 'N'"; // 是否支持抽奖 should remove
$sql .= ",remark_notice char(1) not null default 'N'";
$sql .= ",tags text";
$sql .= ",read_num int not null default 0"; // 阅读数
$sql .= ",share_friend_num int not null default 0"; // 分享给好友数
$sql .= ",share_timeline_num int not null default 0"; // 分享朋友圈数
$sql .= ",data_schemas text"; // 登记项定义
$sql .= ",use_site_header char(1) not null default 'Y'"; // 使用站点页眉
$sql .= ",use_site_footer char(1) not null default 'Y'"; // 使用站点页脚
$sql .= ",use_mission_header char(1) not null default 'Y'"; // 使用项目页眉
$sql .= ",use_mission_footer char(1) not null default 'Y'"; // 使用项目页脚
$sql .= ",extattrs text"; //扩展属性
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 活动页面
 */
$sql = "create table if not exists xxt_enroll_page(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",mpid varchar(32) not null";
$sql .= ",aid varchar(40) not null";
$sql .= ",creater varchar(40) not null default ''";
$sql .= ",create_at int not null";
$sql .= ",type char(1) not null default 'V'"; //I:input,V:view
$sql .= ",title varchar(70) not null default ''";
$sql .= ",name varchar(20) not null default ''";
$sql .= ",code_id int not null default 0"; // from xxt_code_page
$sql .= ",code_name varchar(13) not null default ''"; // from xxt_code_page
$sql .= ",check_entry_rule char(1) not null default 'N'"; //
$sql .= ",share_page char(1) not null default 'N'"; // 分享时分享当前页还是分享活动，缺省分享活动
$sql .= ",share_summary varchar(240)"; // 分享时的摘要字段
$sql .= ",autoenroll_onenter char(1) not null default 'N'"; // 进入时自动登记
$sql .= ",autoenroll_onshare char(1) not null default 'N'"; // 分享时自动登记
$sql .= ",seq int not null"; //页面序号
$sql .= ",data_schemas text"; // 登记项定义
$sql .= ",act_schemas text"; // 登记操作定义
$sql .= ",user_schemas text"; // 登记用户信息定义（仅登记页有效）
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * schema cache
 */
$sql = "create table if not exists xxt_enroll_record_schema(";
$sql .= "aid varchar(40) not null";
$sql .= ",create_at int not null";
$sql .= ",id varchar(40) not null";
$sql .= ",title varchar(255) not null";
$sql .= ",type varchar(255) not null";
$sql .= ",v varchar(40) not null";
$sql .= ",l varchar(255) not null";
$sql .= ",primary key(aid,id,v)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 活动轮次
 */
$sql = "create table if not exists xxt_enroll_round(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",rid varchar(13) not null";
$sql .= ",aid varchar(40) not null";
$sql .= ",mpid varchar(32) not null";
$sql .= ",creater varchar(40) not null default ''";
$sql .= ",create_at int not null";
$sql .= ",start_at int not null"; // 轮次开始时间
$sql .= ",end_at int not null"; // 轮次结束时间
$sql .= ",title varchar(70) not null default ''"; // 分享或生成链接时的标题
$sql .= ",summary varchar(240)"; // 分享或生成链接时的摘要
$sql .= ",state tinyint not null default 0"; // 0:新建|1:启用|2:停用
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 登记信息通知接收人
 */
$sql = "create table if not exists xxt_enroll_receiver(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",mpid varchar(32) not null";
$sql .= ",aid varchar(40) not null";
$sql .= ",identity varchar(100) not null";
$sql .= ",idsrc char(2) not null default ''";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 活动登记记录
 */
$sql = "create table if not exists xxt_enroll_record(";
$sql .= "id int not null auto_increment";
$sql .= ",aid varchar(40) not null";
$sql .= ",mpid varchar(32) not null";
$sql .= ",siteid varchar(32) not null default ''";
$sql .= ",rid varchar(13) not null default ''";
$sql .= ",userid varchar(40) not null default ''";
$sql .= ",openid varchar(255) not null default ''"; // should remove
$sql .= ",nickname varchar(255) not null default ''";
$sql .= ",enroll_key varchar(32) not null";
$sql .= ",enroll_at int not null"; // 填写报名信息时间
$sql .= ",signin_at int not null default 0"; // 签到时间
$sql .= ",signin_num int not null default 0"; // 签到次数
$sql .= ",tags text";
$sql .= ",comment text";
$sql .= ",vid varchar(32)"; // should remove
$sql .= ",mid varchar(32)"; // should remove
$sql .= ",score int not null default 0"; // 点赞数 should remove
$sql .= ",remark_num int not null default 0"; // 评论数 should remove
$sql .= ",follower_num int not null default 0"; // 接收邀请的下家
$sql .= ",state tinyint not null default 1"; //0:remove,1:normal,2:as invite log;
$sql .= ",referrer text"; //
$sql .= ",verified char(1) not null default 'N'"; // 记录是否已通过审核
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 登记活动签到记录（应该删除）
 */
$sql = "create table if not exists xxt_enroll_signin_log(";
$sql .= "id int not null auto_increment";
$sql .= ",mpid varchar(32) not null";
$sql .= ",siteid varchar(32) not null default ''";
$sql .= ",aid varchar(40) not null";
$sql .= ",enroll_key varchar(32) not null";
$sql .= ",userid varchar(40) not null default ''";
$sql .= ",nickname varchar(255) not null default ''";
$sql .= ",openid varchar(255) not null default ''";
$sql .= ",signin_at int not null default 0"; // 签到时间
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 活动登记评分（应该删除）
 */
$sql = "create table if not exists xxt_enroll_record_score(";
$sql .= "id int not null auto_increment";
$sql .= ",enroll_key varchar(32) not null";
$sql .= ",openid varchar(255)";
$sql .= ",nickname varchar(255) not null default ''";
$sql .= ",create_at int not null";
$sql .= ",score int not null default 0";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 活动登记评（应该删除）
 */
$sql = "create table if not exists xxt_enroll_record_remark(";
$sql .= "id int not null auto_increment";
$sql .= ",enroll_key varchar(32) not null";
$sql .= ",openid varchar(255)";
$sql .= ",nickname varchar(255) not null default ''";
$sql .= ",create_at int";
$sql .= ",remark text";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 自定义登记数据
 */
$sql = "create table if not exists xxt_enroll_record_data(";
$sql .= "aid varchar(40) not null";
$sql .= ",enroll_key varchar(32) not null";
$sql .= ",name varchar(40) not null";
$sql .= ",value text";
$sql .= ",state tinyint not null default 1"; //0:remove,1:normal
$sql .= ",primary key(aid,enroll_key,name)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 自定义登记数据统计
 */
$sql = "create table if not exists xxt_enroll_record_stat(";
$sql .= "aid varchar(40) not null";
$sql .= ",create_at int not null";
$sql .= ",id varchar(40) not null";
$sql .= ",title varchar(255) not null";
$sql .= ",v varchar(40) not null";
$sql .= ",l varchar(255) not null";
$sql .= ",c int not null";
$sql .= ",primary key(aid,id,v)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/*
 * 通用活动抽奖轮次（需要删除）
 */
$sql = "create table if not exists xxt_enroll_lottery_round(";
$sql .= "aid varchar(40) not null";
$sql .= ",round_id varchar(32) not null";
$sql .= ",create_at int not null";
$sql .= ",title varchar(40) not null";
$sql .= ",autoplay char(1) not null default 'N'"; // 自动抽奖直到达到抽奖次数
$sql .= ",times int not null"; // 抽奖次数
$sql .= ",targets text";
$sql .= ",primary key(aid,round_id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/*
 * 通用活动抽奖结果（需要删除）
 */
$sql = "create table if not exists xxt_enroll_lottery(";
$sql .= "aid varchar(40) not null";
$sql .= ",round_id varchar(32) not null";
$sql .= ",enroll_key varchar(32) not null";
$sql .= ",draw_at int not null";
$sql .= ",openid varchar(255) not null default ''";
$sql .= ",nickname varchar(255) not null default ''";
$sql .= ",primary key(aid,round_id,enroll_key)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/************************************************
 *签到活动
 ************************************************/
/**
 * 签到活动定义
 */
$sql = "create table if not exists xxt_signin(";
$sql .= "id varchar(40) not null";
$sql .= ",siteid varchar(32) not null";
$sql .= ",enroll_app_id varchar(40) not null default ''"; // 关联的登记活动
$sql .= ",creater varchar(40) not null default ''";
$sql .= ",creater_name varchar(255) not null default ''";
$sql .= ",creater_src char(1)";
$sql .= ",create_at int not null";
$sql .= ",modifier varchar(40) not null default ''";
$sql .= ",modifier_name varchar(255) not null default ''";
$sql .= ",modifier_src char(1)";
$sql .= ",modify_at int not null";
$sql .= ",state tinyint not null default 1"; //0:删除,1:配置,2:运行
$sql .= ",title varchar(255) not null default ''";
$sql .= ",summary varchar(240) not null default ''";
$sql .= ",pic text"; // 分享或生成链接时的图片
$sql .= ",mission_id int not null default 0"; // 所属项目
$sql .= ",mission_phase_id varchar(13) not null default ''"; // 所属项目阶段
$sql .= ",entry_rule text"; // 进入规则
$sql .= ",data_schemas text";
$sql .= ",use_site_header char(1) not null default 'Y'"; // 使用站点页眉
$sql .= ",use_site_footer char(1) not null default 'Y'"; // 使用站点页脚
$sql .= ",use_mission_header char(1) not null default 'Y'"; // 使用项目页眉
$sql .= ",use_mission_footer char(1) not null default 'Y'"; // 使用项目页脚
$sql .= ",extattrs text"; //扩展属性
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_group): ' . $mysqli->error;
}
/**
 * 签到活动轮次
 */
$sql = "create table if not exists xxt_signin_round(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",rid varchar(13) not null";
$sql .= ",aid varchar(40) not null";
$sql .= ",creater varchar(40) not null default ''";
$sql .= ",create_at int not null";
$sql .= ",start_at int not null"; // 轮次开始时间
$sql .= ",before_start_code_id int not null default 0";
$sql .= ",end_at int not null"; // 轮次结束时间
$sql .= ",end_start_code_id int not null default 0";
$sql .= ",title varchar(70) not null default ''"; // 分享或生成链接时的标题
$sql .= ",summary varchar(240)"; // 分享或生成链接时的摘要
$sql .= ",pic text"; // 分享或生成链接时的图片
$sql .= ",state tinyint not null default 0"; // 0:新建|1:启用|2:停用
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 签到活动页面
 */
$sql = "create table if not exists xxt_signin_page(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",aid varchar(40) not null";
$sql .= ",creater varchar(40) not null default ''";
$sql .= ",create_at int not null";
$sql .= ",type char(1) not null default 'V'"; //S:signin,V:view
$sql .= ",title varchar(70) not null default ''";
$sql .= ",name varchar(20) not null default ''";
$sql .= ",code_id int not null default 0"; // from xxt_code_page
$sql .= ",code_name varchar(13) not null default ''"; // from xxt_code_page
$sql .= ",seq int not null"; //页面序号
$sql .= ",data_schemas text"; // 登记项定义
$sql .= ",act_schemas text"; // 登记操作定义
$sql .= ",user_schemas text"; // 登记用户信息定义（仅登记页有效）
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 签到登记记录
 */
$sql = "create table if not exists xxt_signin_record(";
$sql .= "id int not null auto_increment";
$sql .= ",aid varchar(40) not null";
$sql .= ",siteid varchar(32) not null default ''";
$sql .= ",userid varchar(40) not null default ''";
$sql .= ",nickname varchar(255) not null default ''";
$sql .= ",enroll_key varchar(32) not null";
$sql .= ",enroll_at int not null"; // 填写报名信息时间
$sql .= ",signin_at int not null default 0"; // 签到时间
$sql .= ",signin_num int not null default 0"; // 签到次数
$sql .= ",tags text";
$sql .= ",comment text";
$sql .= ",state tinyint not null default 1"; //0:remove,1:normal
$sql .= ",referrer text"; // 发起签到的链接
$sql .= ",verified char(1) not null default 'Y'"; // 记录是否已通过审核
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 自定义签到数据
 */
$sql = "create table if not exists xxt_signin_record_data(";
$sql .= "aid varchar(40) not null";
$sql .= ",enroll_key varchar(32) not null";
$sql .= ",name varchar(40) not null";
$sql .= ",value text";
$sql .= ",state tinyint not null default 1"; //0:remove,1:normal
$sql .= ",primary key(aid,enroll_key,name)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 签到活动签到记录
 */
$sql = "create table if not exists xxt_signin_log(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null default ''";
$sql .= ",aid varchar(40) not null";
$sql .= ",rid varchar(13) not null default ''";
$sql .= ",enroll_key varchar(32) not null";
$sql .= ",userid varchar(40) not null default ''";
$sql .= ",nickname varchar(255) not null default ''";
$sql .= ",signin_at int not null default 0"; // 签到时间
$sql .= ",state tinyint not null default 1"; //0:remove,1:normal
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/*****************************************
 * 分组活动
 *****************************************/
$sql = "create table if not exists xxt_group(";
$sql .= "id varchar(40) not null";
$sql .= ",siteid varchar(32) not null";
$sql .= ",creater varchar(40) not null default ''";
$sql .= ",creater_name varchar(255) not null default ''";
$sql .= ",creater_src char(1)";
$sql .= ",create_at int not null";
$sql .= ",modifier varchar(40) not null default ''";
$sql .= ",modifier_name varchar(255) not null default ''";
$sql .= ",modifier_src char(1)";
$sql .= ",modify_at int not null";
$sql .= ",state tinyint not null default 1"; //0:删除,1:配置,2:运行
$sql .= ",title varchar(255) not null default ''";
$sql .= ",summary varchar(240) not null default ''";
$sql .= ",pic text"; // 分享或生成链接时的图片
$sql .= ",mission_id int not null default 0"; // 所属项目
$sql .= ",mission_phase_id varchar(13) not null default ''"; // 所属项目阶段
$sql .= ",scenario varchar(255) not null default ''"; // 分组活动场景
$sql .= ",source_app varchar(255) not null default ''"; // 关联的登记或签到活动
$sql .= ",last_sync_at int not null"; // 最有同步的时间
$sql .= ",group_rule text"; // 分组规则
$sql .= ",data_schemas text";
$sql .= ",tags text";
$sql .= ",page_code_id int not null default 0"; //should remove
$sql .= ",page_code_name varchar(13) not null default ''";
$sql .= ",use_site_header char(1) not null default 'Y'"; // 使用站点页眉
$sql .= ",use_site_footer char(1) not null default 'Y'"; // 使用站点页脚
$sql .= ",use_mission_header char(1) not null default 'Y'"; // 使用项目页眉
$sql .= ",use_mission_footer char(1) not null default 'Y'"; // 使用项目页脚
$sql .= ",extattrs text"; //扩展属性
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_group): ' . $mysqli->error;
}
/**
 * 分组轮次
 */
$sql = "create table if not exists xxt_group_round(";
$sql .= "aid varchar(40) not null";
$sql .= ",round_id varchar(32) not null";
$sql .= ",create_at int not null";
$sql .= ",title varchar(40) not null";
$sql .= ",autoplay char(1) not null default 'N'"; // 自动抽取直到达到抽取次数
$sql .= ",times int not null"; // 抽取次数
$sql .= ",targets text";
$sql .= ",extattrs text"; //扩展属性
$sql .= ",primary key(aid,round_id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 活动登记记录
 */
$sql = "create table if not exists xxt_group_player(";
$sql .= "id int not null auto_increment";
$sql .= ",aid varchar(40) not null";
$sql .= ",siteid varchar(32) not null default ''";
$sql .= ",userid varchar(40) not null default ''";
$sql .= ",nickname varchar(255) not null default ''";
$sql .= ",enroll_key varchar(32) not null";
$sql .= ",enroll_at int not null"; // 填写报名信息时间
$sql .= ",tags text";
$sql .= ",comment text";
$sql .= ",state tinyint not null default 1"; //0:remove,1:normal,2:as invite log
$sql .= ",referrer text"; //
$sql .= ",round_id varchar(32) not null default ''";
$sql .= ",round_title title varchar(40) not null default ''";
$sql .= ",draw_at int not null";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 自定义登记数据
 */
$sql = "create table if not exists xxt_group_player_data(";
$sql .= "aid varchar(40) not null";
$sql .= ",enroll_key varchar(32) not null";
$sql .= ",name varchar(40) not null";
$sql .= ",value text";
$sql .= ",state tinyint not null default 1"; //0:remove,1:normal
$sql .= ",primary key(aid,enroll_key,name)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}

echo 'finish enroll.' . PHP_EOL;