<?php
require_once '../db.php';
/*********************************************
 *登记活动
 *********************************************/
$sql = "create table if not exists xxt_enroll(";
$sql .= "id varchar(40) not null";
$sql .= ",siteid varchar(32) not null";
$sql .= ",creater varchar(40) not null default ''";
$sql .= ",creater_name varchar(255) not null default ''"; //from account or fans
$sql .= ",creater_src char(1)"; //A:accouont|F:fans
$sql .= ",create_at int not null";
$sql .= ",modifier varchar(40) not null default ''"; //accountid/fid
$sql .= ",modifier_name varchar(255) not null default ''"; //from account or fans
$sql .= ",modifier_src char(1)"; //A:accouont|F:fans|M:member
$sql .= ",modify_at int not null";
$sql .= ",state tinyint not null default 1"; //0:删除,1:配置,2:运行
$sql .= ",title varchar(255) not null default ''";
$sql .= ",summary varchar(240) not null default ''"; // 分享或生成链接时的摘要
$sql .= ",pic text"; // 分享或生成链接时的图片
$sql .= ",mission_id int not null default 0"; // 所属项目
$sql .= ",scenario varchar(255) not null default ''"; // 登记活动场景
$sql .= ",scenario_config text"; // 登记活动场景的配置参数
$sql .= ",round_cron text"; // 定时创建轮次规则
$sql .= ",count_limit int not null default 0"; // 限制登记次数，0不限制
$sql .= ",start_at int not null default 0"; // 开始时间
$sql .= ",before_start_page varchar(20) not null default ''";
$sql .= ",end_submit_at int not null default 0"; // 结束提交时间
$sql .= ",end_at int not null default 0"; // 结束时间
$sql .= ",after_end_page varchar(20) not null default ''";
$sql .= ",access_control char(1) not null default 'N'";
$sql .= ",authapis text";
$sql .= ",entry_rule text"; // 参与规则
$sql .= ",enrolled_entry_page varchar(20) not null default ''";
$sql .= ",open_lastroll char(1) not null default 'Y'"; // 打开最后一条登记记录，还是编辑新的
$sql .= ",multi_rounds char(1) not null default 'N'"; // 支持轮次
$sql .= ",notify_submit char(1) not null default 'N'"; // 是否发送提交事件通知
$sql .= ",can_repos char(1) not null default 'N'"; // 打开共享页
$sql .= ",repos_unit char(1) not null default 'D'"; // 共享页按数据（D）还是按记录（R）显示
$sql .= ",can_rank char(1) not null default 'N'"; // 打开排行页
$sql .= ",can_coin char(1) not null default 'N'"; // 是否支持积分
$sql .= ",can_coinpay char(1) not null default 'N'"; // 是否可以进行打赏
$sql .= ",can_siteuser char(1) not null default 'N'"; // 是否可以进入用户主页
$sql .= ",can_cowork char(1) not null default 'N'"; // 是否支持多人修改同一条登记记录
$sql .= ",can_autoenroll char(1) not null default 'N'"; // 是否支持自动登记
$sql .= ",remark_notice char(1) not null default 'N'"; // 支持评论提醒
$sql .= ",assigned_nickname text null"; // 填写题目中指定填写人昵称{"valid":"Y","schema":{"id":"xxxxxx"}}
$sql .= ",tags text null"; // 登记记录标签
$sql .= ",category_tags text null"; // 素材分类标签
$sql .= ",enroll_app_id varchar(40) not null default ''"; // 关联的登记活动
$sql .= ",group_app_id varchar(40) not null default ''"; // 关联的分组活动
$sql .= ",read_num int not null default 0"; // 阅读数
$sql .= ",share_friend_num int not null default 0"; // 分享给好友数
$sql .= ",share_timeline_num int not null default 0"; // 分享朋友圈数
$sql .= ",data_schemas longtext null"; // 登记项定义
$sql .= ",use_site_header char(1) not null default 'Y'"; // 使用站点页眉
$sql .= ",use_site_footer char(1) not null default 'Y'"; // 使用站点页脚
$sql .= ",use_mission_header char(1) not null default 'Y'"; // 使用项目页眉
$sql .= ",use_mission_footer char(1) not null default 'Y'"; // 使用项目页脚
$sql .= ",extattrs text"; //扩展属性
$sql .= ",template_id int not null default 0"; // 通过哪个模板创建
$sql .= ",template_version varchar(10) not null default ''"; //模板版本号
$sql .= ",op_short_url_code char(4) not null default ''"; // 运营管理页面的短链接编码
$sql .= ",rp_short_url_code char(4) not null default ''"; // 统计报告页面的短链接编码
$sql .= ",rp_config text null"; // 统计报告页面用户选择的标识信息
$sql .= ",rank_config text null"; // 排行榜页面设置信息
$sql .= ",matter_mg_tag varchar(255) not null default ''";
$sql .= ",absent_cause text null";
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
$sql .= ",aid varchar(40) not null";
$sql .= ",creater varchar(40) not null default ''";
$sql .= ",create_at int not null";
$sql .= ",type char(1) not null default 'V'"; //I:input,V:view
$sql .= ",title varchar(70) not null default ''";
$sql .= ",name varchar(20) not null default ''";
$sql .= ",code_id int not null default 0"; // from xxt_code_page
$sql .= ",code_name varchar(13) not null default ''"; // from xxt_code_page
$sql .= ",share_page char(1) not null default 'Y'"; // 分享时分享当前页还是分享活动，缺省分享活动
$sql .= ",share_summary varchar(240) not null default ''"; // 分享时的摘要字段
$sql .= ",autoenroll_onenter char(1) not null default 'N'"; // 进入时自动登记
$sql .= ",autoenroll_onshare char(1) not null default 'N'"; // 分享时自动登记
$sql .= ",seq int not null"; //页面序号
$sql .= ",data_schemas longtext"; // 登记项定义
$sql .= ",act_schemas text"; // 登记操作定义
$sql .= ",user_schemas text"; // 登记用户信息定义（仅登记页有效）
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 登记活动轮次
 */
$sql = "create table if not exists xxt_enroll_round(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",rid varchar(13) not null";
$sql .= ",aid varchar(40) not null";
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
$sql .= ",aid varchar(40) not null";
$sql .= ",join_at int not null default 0"; // 加入时间
$sql .= ",userid varchar(40) not null default ''";
$sql .= ",nickname varchar(255) not null default ''";
$sql .= ",sns_user text"; // 社交账号信息
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
$sql .= ",siteid varchar(32) not null default ''";
$sql .= ",rid varchar(13) not null default ''";
$sql .= ",group_id varchar(32) not null default ''"; // 用户分组id
$sql .= ",userid varchar(40) not null default ''";
$sql .= ",nickname varchar(255) not null default ''";
$sql .= ",wx_openid varchar(255) not null default ''";
$sql .= ",yx_openid varchar(255) not null default ''";
$sql .= ",qy_openid varchar(255) not null default ''";
$sql .= ",headimgurl varchar(255) not null default ''";
$sql .= ",enroll_key varchar(32) not null";
$sql .= ",enroll_at int not null"; // 填写报名信息时间
$sql .= ",first_enroll_at int not null"; // 填写报名信息时间
$sql .= ",tags text null";
$sql .= ",data_tag text null";
$sql .= ",comment text null";
$sql .= ",remark_num int not null default 0"; // 评论数
$sql .= ",state tinyint not null default 1"; //0:clean,1:normal,2:as invite log,100:后台删除,101:用户删除;
$sql .= ",referrer text null"; // should be removed
$sql .= ",data longtext null"; // 登记的数据项
$sql .= ",supplement longtext null"; // 补充说明
$sql .= ",score text null"; // 测试活动，登记记录的得分
$sql .= ",verified char(1) not null default 'N'"; // 记录是否已通过审核
$sql .= ",matched_enroll_key varchar(32) not null default ''"; // 如果关联了登记活动，记录关联的登记记录
$sql .= ",group_enroll_key varchar(32) not null default ''"; // 如果关联了分组活动，记录关联的分组记录
$sql .= ",submit_log text null"; // 数据提交日志
$sql .= ",agreed char(1) not null default ''"; // 是否赞同（Y：推荐，N：屏蔽，A(ccept)：接受）
$sql .= ",agreed_log text null"; // 推荐日志
$sql .= ",like_log longtext"; // 点赞日志 {userid:likeAt}
$sql .= ",like_num int not null default 0"; // 点赞数
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 登记项的数据
 */
$sql = "create table if not exists xxt_enroll_record_data(";
$sql .= "id int not null auto_increment";
$sql .= ",aid varchar(40) not null";
$sql .= ",rid varchar(13) not null default ''";
$sql .= ",group_id varchar(32) not null default ''"; // 用户分组id
$sql .= ",enroll_key varchar(32) not null";
$sql .= ",submit_at int not null default 0"; // 数据的提交时间，和modify_log中的数据对应
$sql .= ",userid varchar(40) not null default ''";
$sql .= ",schema_id varchar(40) not null";
$sql .= ",multitext_seq int not null default 0";
$sql .= ",value text";
$sql .= ",tag text"; // 标签的id，json格式的数组
$sql .= ",supplement text"; // 补充说明
$sql .= ",state tinyint not null default 1"; //0:remove,1:normal
$sql .= ",remark_num int not null default 0"; // 评论数
$sql .= ",last_remark_at int not null default 0"; // 最后一次被评论的时间
$sql .= ",score float not null default 0"; // 登记项获得的分数
$sql .= ",modify_log longtext"; // 数据修改日志
$sql .= ",like_log longtext"; // 点赞日志 {userid:likeAt}
$sql .= ",like_num int not null default 0"; // 点赞数
$sql .= ",agreed char(1) not null default ''"; // 是否赞同（Y：推荐，N：屏蔽，A(ccept)：接受）
$sql .= ",agreed_log text null"; // 推荐日志
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 自定义登记数据统计
 */
$sql = "create table if not exists xxt_enroll_record_stat(";
$sql .= "siteid varchar(32) not null";
$sql .= ",aid varchar(40) not null";
$sql .= ",rid varchar(13) not null default ''";
$sql .= ",create_at int not null";
$sql .= ",id varchar(40) not null";
$sql .= ",title varchar(255) not null";
$sql .= ",v varchar(40) not null";
$sql .= ",l varchar(255) not null";
$sql .= ",c double not null";
$sql .= ") ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 登记活动内容评论
 */
$sql = "create table if not exists xxt_enroll_record_remark(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",aid varchar(40) not null";
$sql .= ",rid varchar(13) not null default ''";
$sql .= ",enroll_group_id varchar(32) not null default ''"; // 被评论内容所属的用户分组id
$sql .= ",enroll_key varchar(32) not null"; // 被评论的记录
$sql .= ",enroll_userid varchar(40) not null default ''"; // 提交登记记录的人
$sql .= ",group_id varchar(32) not null default ''"; // 发表评论的人所属用户分组id
$sql .= ",userid varchar(40) not null default ''"; // 发表评论的人
$sql .= ",user_src char(1) not null default 'S'"; // 用户来源团队用户账号（Platform）或个人用户账号（Site）；没用了，userid已经统一了
$sql .= ",nickname varchar(255) not null default ''";
$sql .= ",create_at int";
$sql .= ",content text";
$sql .= ",schema_id varchar(40) not null default ''"; // 针对某条登记记录的某个登记项的评论
$sql .= ",data_id int not null default 0"; // xxt_enroll_record_data的id
$sql .= ",remark_id int not null default 0"; // 是对哪条评论进行的评论
$sql .= ",like_log longtext"; // 点赞日志 {userid:likeAt}
$sql .= ",like_num int not null default 0"; // 点赞数
$sql .= ",agreed char(1) not null default ''"; // 是否赞同（Y：推荐，N：屏蔽，A(ccept)：接受）
$sql .= ",state tinyint not null default 1"; //0:clean,1:normal,2:as invite log,100:后台删除,101:用户删除;
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 登记活动的参与人及行为汇总，包含：登记人和评论人
 */
$sql = "create table if not exists xxt_enroll_user(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",aid varchar(40) not null";
$sql .= ",rid varchar(13) not null default ''"; // 登记轮次，ALL代表累计的数据，每个轮次有单独轮次的记录，如果没有设置轮次，轮次rid为空字符串
$sql .= ",group_id varchar(32) not null default ''"; // 用户分组id
$sql .= ",userid varchar(40) not null default ''";
$sql .= ",nickname varchar(255) not null default ''";
$sql .= ",last_enroll_at int not null default 0"; // 最后一次登记时间
$sql .= ",enroll_num int not null default 0"; // 登记记录的条数
$sql .= ",last_remark_at int not null default 0"; // 最后一次获得评价的时间
$sql .= ",remark_num int not null default 0"; // 获得的评价条数
$sql .= ",last_like_at int not null default 0"; // 登记内容最后一次获得点赞的时间
$sql .= ",like_num int not null default 0"; // 登记内容获得点赞的次数
$sql .= ",last_like_remark_at int not null default 0"; // 评论最后一次获得点赞的时间
$sql .= ",like_remark_num int not null default 0"; // 评论获得点赞的次数
$sql .= ",last_remark_other_at int not null default 0"; // 最后一次发表评价的时间
$sql .= ",remark_other_num int not null default 0"; // 发表的评价条数
$sql .= ",last_like_other_at int not null default 0"; // 最后一次对登记内容进行点赞的时间
$sql .= ",like_other_num int not null default 0"; // 对登记内容进行点赞的次数
$sql .= ",last_like_other_remark_at int not null default 0"; // 最后一次对评论进行点赞的时间
$sql .= ",like_other_remark_num int not null default 0"; // 对评论进行点赞的次数
$sql .= ",last_recommend_at int not null default 0"; // 最后一次获得推荐的时间
$sql .= ",recommend_num int not null default 0"; // 获得推荐的次数
$sql .= ",user_total_coin int not null default 0"; // 用户在活动中的轮次上的总积分
$sql .= ",score float default 0 COMMENT '得分'"; //
$sql .= ",state tinyint not null default 1"; //0:clean,1:normal,2:as invite log,100:后台删除,101:用户删除;
$sql .= ",modify_log longtext null"; // 数据修改日志
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/*
 * 登记活动标签
 */
$sql = 'create table if not exists xxt_enroll_record_tag(';
$sql .= 'id int not null auto_increment';
$sql .= ",siteid varchar(32) not null default ''";
$sql .= ",aid varchar(40) not null";
$sql .= ",create_at int not null default 0"; //
$sql .= ",creater varchar(40) not null default ''"; // 如果是参与人标签，为userid；如果是发起人标签，为uid
$sql .= ",creater_src char(1) not null default 'S'"; // S:填写端用户；P:发起方用户；O:管理方用户
$sql .= ',label varchar(255) not null';
$sql .= ',level int not null default 0'; // 标签的层级
$sql .= ",seq int not null default 0"; // 标签的顺序
$sql .= ",use_num int not null default 0"; // 使用次数
$sql .= ",scope char(1) not null default 'U'"; // 使用范围，U：参与人，I：发起人
$sql .= ",state tinyint not null default 1"; //0:clean,1:normal,2:as invite log,100:后台删除,101:用户删除;
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
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
$sql .= ",group_app_id varchar(40) not null default ''"; // 关联的分组活动
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
$sql .= ",start_at int not null default 0"; // 开始时间
$sql .= ",end_at int not null default 0"; // 结束时间
$sql .= ",category_tags text"; // 素材分类标签
$sql .= ",mission_id int not null default 0"; // 所属项目
$sql .= ",entry_rule text"; // 进入规则
$sql .= ",data_schemas text";
$sql .= ",assigned_nickname text"; // 填写题目中指定填写人昵称{"valid":"Y","schema":{"id":"xxxxxx"}}
$sql .= ",use_site_header char(1) not null default 'Y'"; // 使用站点页眉
$sql .= ",use_site_footer char(1) not null default 'Y'"; // 使用站点页脚
$sql .= ",use_mission_header char(1) not null default 'Y'"; // 使用项目页眉
$sql .= ",use_mission_footer char(1) not null default 'Y'"; // 使用项目页脚
$sql .= ",extattrs text"; //扩展属性
$sql .= ",tags text";
$sql .= ",op_short_url_code char(4) not null default ''"; // 运营管理页面的短链接编码
$sql .= ",notify_submit char(1) not null default 'N'"; // 是否发送提交事件通知
$sql .= ",matter_mg_tag varchar(255) not null default ''";
$sql .= ",absent_cause text";
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
$sql .= ",start_at int not null default 0"; // 轮次开始时间
$sql .= ",before_start_code_id int not null default 0";
$sql .= ",end_at int not null default 0"; // 轮次结束时间
$sql .= ",after_end_code_id int not null default 0";
$sql .= ",late_at int not null default 0"; // 轮次迟到时间
$sql .= ",after_late_code_id int not null default 0";
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
$sql .= ",wx_openid varchar(255) not null default ''";
$sql .= ",yx_openid varchar(255) not null default ''";
$sql .= ",qy_openid varchar(255) not null default ''";
$sql .= ",headimgurl varchar(255) not null default ''";
$sql .= ",enroll_key varchar(32) not null";
$sql .= ",enroll_at int not null"; // 填写报名信息时间
$sql .= ",signin_at int not null default 0"; // 签到时间
$sql .= ",signin_num int not null default 0"; // 签到次数
$sql .= ",signin_log text"; // 签到日志
$sql .= ",tags text";
$sql .= ",comment text";
$sql .= ",state tinyint not null default 1"; //0:remove,1:normal
$sql .= ",referrer text"; // 发起签到的链接
$sql .= ",data text"; // 登记的数据项
$sql .= ",verified char(1) not null default 'Y'"; // 记录是否已通过审核
$sql .= ",verified_enroll_key varchar(32) not null default ''"; // 如果是通过了报名表的验证，记录关联的报名记录
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
 * 登记信息通知接收人
 */
$sql = "create table if not exists xxt_signin_receiver(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",aid varchar(40) not null";
$sql .= ",join_at int not null default 0"; // 加入时间
$sql .= ",userid varchar(40) not null default ''";
$sql .= ",nickname varchar(255) not null default ''";
$sql .= ",sns_user text"; // 社交账号信息
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
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
$sql .= ",start_at int not null default 0"; // 开始时间
$sql .= ",end_at int not null default 0"; // 结束时间
$sql .= ",category_tags text"; // 素材分类标签
$sql .= ",mission_id int not null default 0"; // 所属项目
$sql .= ",scenario varchar(255) not null default ''"; // 分组活动场景
$sql .= ",source_app varchar(255) not null default ''"; // 关联的登记或签到活动，{"id":"579e9f186a859","type":"signin"}
$sql .= ",last_sync_at int not null default 0"; // 最后同步的时间
$sql .= ",group_rule text"; // 分组规则
$sql .= ",data_schemas text";
$sql .= ",assigned_nickname text"; // 导入活动中，填写题目中指定填写人昵称{"valid":"Y","schema":{"id":"xxxxxx"}}
$sql .= ",tags text";
$sql .= ",page_code_id int not null default 0"; //should remove
$sql .= ",page_code_name varchar(13) not null default ''";
$sql .= ",use_site_header char(1) not null default 'Y'"; // 使用站点页眉
$sql .= ",use_site_footer char(1) not null default 'Y'"; // 使用站点页脚
$sql .= ",use_mission_header char(1) not null default 'Y'"; // 使用项目页眉
$sql .= ",use_mission_footer char(1) not null default 'Y'"; // 使用项目页脚
$sql .= ",extattrs text"; //扩展属性
$sql .= ",op_short_url_code char(4) not null default ''"; // 运营管理页面的短链接编码
$sql .= ",matter_mg_tag varchar(255) not null default ''";
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
 * 分组活动用户记录
 */
$sql = "create table if not exists xxt_group_player(";
$sql .= "id int not null auto_increment";
$sql .= ",aid varchar(40) not null";
$sql .= ",siteid varchar(32) not null default ''";
$sql .= ",userid varchar(40) not null default ''";
$sql .= ",nickname varchar(255) not null default ''";
$sql .= ",wx_openid varchar(255) not null default ''";
$sql .= ",yx_openid varchar(255) not null default ''";
$sql .= ",qy_openid varchar(255) not null default ''";
$sql .= ",headimgurl varchar(255) not null default ''";
$sql .= ",is_leader char(1) not null default 'N'"; // 是否为组长
$sql .= ",enroll_key varchar(32) not null";
$sql .= ",enroll_at int not null"; // 填写报名信息时间
$sql .= ",tags text";
$sql .= ",comment text";
$sql .= ",state tinyint not null default 1"; //0:remove,1:normal,2:as invite log
$sql .= ",referrer text"; //
$sql .= ",round_id varchar(32) not null default ''";
$sql .= ",round_title varchar(40) not null default ''";
$sql .= ",draw_at int not null";
$sql .= ",data text"; // 登记的数据项
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
$sql .= ",primary key(aid,enroll_key,name,state)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}

echo 'finish enroll.' . PHP_EOL;