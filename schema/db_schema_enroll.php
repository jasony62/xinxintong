<?php
require_once '../db.php';
/*********************************************
 *记录活动
 *********************************************/
$sql = "create table if not exists xxt_enroll(";
$sql .= "id varchar(40) not null";
$sql .= ",siteid varchar(32) not null";
$sql .= ",creater varchar(40) not null default ''";
$sql .= ",creater_name varchar(255) not null default ''"; //from account or fans
$sql .= ",create_at int not null";
$sql .= ",modifier varchar(40) not null default ''"; //accountid/fid
$sql .= ",modifier_name varchar(255) not null default ''"; //from account or fans
$sql .= ",modify_at int not null";
$sql .= ",state tinyint not null default 1"; //0:删除,1:配置,2:运行
$sql .= ",title varchar(255) not null default ''";
$sql .= ",summary varchar(240) not null default ''"; // 分享或生成链接时的摘要
$sql .= ",pic text null"; // 分享或生成链接时的图片
$sql .= ",mission_id int not null default 0"; // 所属项目
$sql .= ",scenario varchar(255) not null default ''"; // 记录活动场景
$sql .= ",scenario_config text null"; // 记录活动场景的配置参数
$sql .= ",round_cron text null"; // 定时创建轮次规则
$sql .= ",sync_mission_round char(1) not null default 'N'"; // 和项目轮次同步
$sql .= ",count_limit int not null default 0"; // 限制登记次数，0不限制
$sql .= ",start_at int not null default 0"; // 开始时间
$sql .= ",before_start_page varchar(20) not null default ''";
$sql .= ",end_at int not null default 0"; // 结束时间
$sql .= ",after_end_page varchar(20) not null default ''";
$sql .= ",entry_rule text null"; // 进入规则
$sql .= ",action_rule text null"; // 行动规则
$sql .= ",enrolled_entry_page varchar(20) not null default ''";
$sql .= ",open_lastroll char(1) not null default 'Y'"; // 打开最后一条登记记录，还是编辑新的
$sql .= ",can_autoenroll char(1) not null default 'N'"; // 是否支持自动登记
$sql .= ",notify_config text null"; // 通知提醒设置
$sql .= ",assigned_nickname text null"; // 填写题目中指定填写人昵称{"valid":"Y","schema":{"id":"xxxxxx"}}
$sql .= ",tags text null"; // 登记记录标签
$sql .= ",category_tags text null"; // 素材分类标签
$sql .= ",read_num int not null default 0"; // 阅读数
$sql .= ",share_friend_num int not null default 0"; // 分享给好友数
$sql .= ",share_timeline_num int not null default 0"; // 分享朋友圈数
$sql .= ",data_schemas longtext null"; // 登记项定义
$sql .= ",recycle_schemas longtext null"; // 放入回收站的定义
$sql .= ",use_site_header char(1) not null default 'Y'"; // 使用站点页眉
$sql .= ",use_site_footer char(1) not null default 'Y'"; // 使用站点页脚
$sql .= ",use_mission_header char(1) not null default 'Y'"; // 使用项目页眉
$sql .= ",use_mission_footer char(1) not null default 'Y'"; // 使用项目页脚
$sql .= ",extattrs text null"; //扩展属性
$sql .= ",template_id int not null default 0"; // 通过哪个模板创建
$sql .= ",template_version varchar(10) not null default ''"; //模板版本号
$sql .= ",rp_config text null"; // 统计报告页面用户选择的标识信息
$sql .= ",repos_config text null"; // 共享页页面设置信息
$sql .= ",rank_config text null"; // 排行榜页面设置信息
$sql .= ",matter_mg_tag varchar(255) not null default ''";
$sql .= ",absent_cause text null";
$sql .= ",wxacode_url text null"; // 微信小程序
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
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 记录活动轮次
 */
$sql = "create table if not exists xxt_enroll_round(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",rid varchar(13) not null";
$sql .= ",aid varchar(40) not null";
$sql .= ",creator varchar(40) not null default ''";
$sql .= ",create_at int not null";
$sql .= ",start_at int not null"; // 轮次开始时间
$sql .= ",end_at int not null"; // 轮次结束时间
$sql .= ",title varchar(70) not null default ''"; // 分享或生成链接时的标题
$sql .= ",summary varchar(240)"; // 分享或生成链接时的摘要
$sql .= ",state tinyint not null default 0"; // 0:新建|1:启用|2:停用|100:删除
$sql .= ",purpose char(1) not null default 'C'"; // Common:常规的|Baseline:基准的|Summary:汇总的
$sql .= ",mission_rid varchar(13) not null default ''"; // 关联的项目轮次
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
$sql .= ",purpose char(1) not null default 'C'"; // Common:常规的|Baseline:基准的|Summary:汇总的
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
$sql .= ",remark_num int not null default 0"; // 留言数
$sql .= ",rec_remark_num int not null default 0"; // 留言数
$sql .= ",state tinyint not null default 1"; //0:clean,1:normal,2:as invite log,100:后台删除,101:用户删除;
$sql .= ",referrer text null"; // should be removed
$sql .= ",data longtext null"; // 登记的数据项
$sql .= ",supplement longtext null"; // 补充说明
$sql .= ",score text null"; // 测试活动，登记记录的得分
$sql .= ",verified char(1) not null default 'N'"; // 记录是否已通过审核
$sql .= ",matched_enroll_key varchar(32) not null default ''"; // 如果关联了记录活动，记录关联的登记记录
$sql .= ",group_enroll_key varchar(32) not null default ''"; // 如果关联了分组活动，记录关联的分组记录
$sql .= ",submit_log text null"; // 数据提交日志
$sql .= ",agreed char(1) not null default ''"; // 是否赞同（Y：推荐，N：屏蔽，A(ccept)：接受，D(iscuss)）
$sql .= ",agreed_log text null"; // 推荐日志
$sql .= ",like_log longtext"; // 点赞日志 {userid:likeAt}
$sql .= ",like_num int not null default 0"; // 点赞数
$sql .= ",like_data_num int not null default 0"; // 记录的数据点赞数
$sql .= ",dislike_log longtext"; // 反对日志 {userid:dislikeAt}
$sql .= ",dislike_num int not null default 0"; // 反对数
$sql .= ",dislike_data_num int not null default 0"; // 记录的数据反对数
$sql .= ",favor_num int not null default 0"; // 收藏数
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
$sql .= ",purpose char(1) not null default 'C'"; // Common:常规的|Baseline:基准的|Summary:汇总的
$sql .= ",group_id varchar(32) not null default ''"; // 用户分组id
$sql .= ",enroll_key varchar(32) not null";
$sql .= ",submit_at int not null default 0"; // 数据的提交时间，和modify_log中的数据对应
$sql .= ",userid varchar(40) not null default ''";
$sql .= ",nickname varchar(255) not null default ''";
$sql .= ",schema_id varchar(40) not null";
$sql .= ",multitext_seq int not null default 0";
$sql .= ",value text null";
$sql .= ",tag text null"; // 标签的id，json格式的数组
$sql .= ",supplement text null"; // 补充说明
$sql .= ",state tinyint not null default 1"; //0:remove,1:normal
$sql .= ",remark_num int not null default 0"; // 留言数
$sql .= ",last_remark_at int not null default 0"; // 最后一次被留言的时间
$sql .= ",score float not null default 0"; // 登记项获得的分数
$sql .= ",modify_log longtext null"; // 数据修改日志
$sql .= ",like_log longtext null"; // 点赞日志 {userid:likeAt}
$sql .= ",like_num int not null default 0"; // 点赞数
$sql .= ",dislike_log longtext null"; // 点赞日志 {userid:likeAt}
$sql .= ",dislike_num int not null default 0"; // 点赞数
$sql .= ",agreed char(1) not null default ''"; // 是否赞同（Y：推荐，N：屏蔽，A(ccept)：接受）
$sql .= ",agreed_log text null"; // 推荐日志
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 记录所属的轮次，支持1条记录出现在多个轮次
 */
$sql = "create table if not exists xxt_enroll_record_round(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",aid varchar(40) not null";
$sql .= ",rid varchar(13) not null";
$sql .= ",enroll_key varchar(32) not null";
$sql .= ",userid varchar(40) not null default ''";
$sql .= ",add_at int not null"; // 加入轮次的时间
$sql .= ",add_cause char(1) not null"; // 加入的原因 Create:创建新记录，Revise：修订
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 活动登记记录间的关联
 */
$sql = "create table if not exists xxt_enroll_assoc(";
$sql .= "id bigint not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",aid varchar(40) not null";
$sql .= ",record_id int not null";
$sql .= ",entity_a_id int not null";
$sql .= ',entity_a_type tinyint not null';
$sql .= ",entity_b_id int not null";
$sql .= ',entity_b_type tinyint not null';
$sql .= ",assoc_mode tinyint not null default 0";
$sql .= ",assoc_num int not null default 0";
$sql .= ",public char(1) not null default 'Y'";
$sql .= ",first_assoc_at int not null";
$sql .= ",last_assoc_at int not null";
$sql .= ",assoc_text text null"; // 关联描述
$sql .= ",assoc_reason varchar(255) not null default ''"; // 关联理由
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 记录活动标签指定记录
 */
$sql = 'create table if not exists xxt_enroll_assoc_log(';
$sql .= 'id bigint not null auto_increment';
$sql .= ',siteid varchar(32) not null';
$sql .= ',aid varchar(40) not null';
$sql .= ',record_id int not null';
$sql .= ',assoc_id bigint not null';
$sql .= ",assoc_text text null";
$sql .= ",assoc_reason varchar(255) not null default ''"; // 关联理由
$sql .= ',userid varchar(40) not null';
$sql .= ',link_at int not null default 0';
$sql .= ',unlink_at int not null default 0';
$sql .= ',undo_log_id bigint not null default 0';
$sql .= ",state tinyint not null default 1";
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
$sql .= ",l text null";
$sql .= ",c double not null";
$sql .= ") ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 记录活动内容留言
 */
$sql = "create table if not exists xxt_enroll_record_remark(";
$sql .= "id int not null auto_increment";
$sql .= ",seq_in_record int not null default 0"; // 留言在记录中的序号
$sql .= ",seq_in_data int not null default 0"; // 留言在数据中的序号
$sql .= ",siteid varchar(32) not null";
$sql .= ",aid varchar(40) not null";
$sql .= ",rid varchar(13) not null default ''";
$sql .= ",enroll_group_id varchar(32) not null default ''"; // 被留言内容所属的用户分组id
$sql .= ",enroll_key varchar(32) not null"; // 被留言的记录
$sql .= ",enroll_userid varchar(40) not null default ''"; // 提交登记记录的人
$sql .= ",group_id varchar(32) not null default ''"; // 发表留言的人所属用户分组id
$sql .= ",userid varchar(40) not null default ''"; // 发表留言的人
$sql .= ",nickname varchar(255) not null default ''";
$sql .= ",create_at int not null";
$sql .= ",modify_at int not null default 0";
$sql .= ",content text null";
$sql .= ",schema_id varchar(40) not null default ''"; // 针对某条登记记录的某个登记项的留言
$sql .= ",data_id int not null default 0"; // xxt_enroll_record_data的id
$sql .= ",remark_id int not null default 0"; // 是对哪条留言进行的留言
$sql .= ",remark_num int not null default 0"; // 留言数
$sql .= ",like_log longtext null"; // 点赞日志 {userid:likeAt}
$sql .= ",like_num int not null default 0"; // 点赞数
$sql .= ",dislike_log longtext null"; // 点赞日志 {userid:likeAt}
$sql .= ",dislike_num int not null default 0"; // 点赞数
$sql .= ",agreed char(1) not null default ''"; // 是否赞同（Y：推荐，N：屏蔽，A(ccept)：接受）
$sql .= ",agreed_log text null"; // 推荐日志
$sql .= ",state tinyint not null default 1"; //0:clean,1:normal,2:as invite log,100:后台删除,101:用户删除;
$sql .= ",modify_log longtext null"; // 数据修改日志
$sql .= ",as_cowork_id int not null default 0"; // 作为协作数据后对应的协作数据id
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 记录活动的参与人及行为汇总，包含：登记人和留言人
 */
$sql = "create table if not exists xxt_enroll_user(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",aid varchar(40) not null";
$sql .= ",rid varchar(13) not null default ''"; // 登记轮次，ALL代表累计的数据，每个轮次有单独轮次的记录，如果没有设置轮次，轮次rid为空字符串
$sql .= ",purpose char(1) not null default 'C'"; // Common:常规的|Baseline:基准的|Summary:汇总的
$sql .= ",group_id varchar(32) not null default ''"; // 用户分组id
$sql .= ",userid varchar(40) not null default ''";
$sql .= ",nickname varchar(255) not null default ''";
$sql .= ",entry_num int not null default 0"; // 进入活动的次数
$sql .= ",last_entry_at int not null default 0"; // 最后一次进入时间
$sql .= ",total_elapse int not null default 0"; // 参与活动的总时长
$sql .= ",last_enroll_at int not null default 0"; // 最后一次登记时间
$sql .= ",enroll_num int not null default 0"; // 登记记录的条数
$sql .= ",revise_num int not null default 0"; // 跨轮次修订的次数
$sql .= ",last_cowork_at int not null default 0"; // 最后一次获得协作填写时间
$sql .= ",cowork_num int not null default 0"; // 获得协作填写的数量
$sql .= ",last_do_cowork_at int not null default 0"; // 最后一次进行协作填写时间
$sql .= ",do_cowork_num int not null default 0"; // 进行协作填写的数量
$sql .= ",last_remark_at int not null default 0"; // 最后一次获得评价的时间
$sql .= ",remark_num int not null default 0"; // 获得的评价条数
$sql .= ",last_remark_cowork_at int not null default 0"; // 最后一次协作填写获得留言的时间
$sql .= ",remark_cowork_num int not null default 0"; // 协作填写获得的评价条数
$sql .= ",last_like_at int not null default 0"; // 登记内容最后一次获得点赞的时间
$sql .= ",like_num int not null default 0"; // 登记内容获得点赞的次数
$sql .= ",last_like_cowork_at int not null default 0"; // 协作填写最后一次获得点赞的时间
$sql .= ",like_cowork_num int not null default 0"; // 协作填写获得点赞的次数
$sql .= ",last_like_remark_at int not null default 0"; // 留言最后一次获得点赞的时间
$sql .= ",like_remark_num int not null default 0"; // 留言获得点赞的次数
$sql .= ",last_do_remark_at int not null default 0"; // 最后一次发表评价的时间
$sql .= ",do_remark_num int not null default 0"; // 发表的评价条数
$sql .= ",last_do_like_at int not null default 0"; // 最后一次对登记内容进行点赞的时间
$sql .= ",do_like_num int not null default 0"; // 对登记内容进行点赞的次数
$sql .= ",last_do_like_cowork_at int not null default 0"; // 最后一次对协作进行点赞的时间
$sql .= ",do_like_cowork_num int not null default 0"; // 对协作进行点赞的次数
$sql .= ",last_do_like_remark_at int not null default 0"; // 最后一次对留言进行点赞的时间
$sql .= ",do_like_remark_num int not null default 0"; // 对留言进行点赞的次数
$sql .= ",last_dislike_at int not null default 0"; // 登记内容最后一次获得点赞的时间
$sql .= ",dislike_num int not null default 0"; // 登记内容获得点赞的次数
$sql .= ",last_dislike_cowork_at int not null default 0"; // 协作填写最后一次获得点赞的时间
$sql .= ",dislike_cowork_num int not null default 0"; // 协作填写获得点赞的次数
$sql .= ",last_dislike_remark_at int not null default 0"; // 留言最后一次获得点赞的时间
$sql .= ",dislike_remark_num int not null default 0"; // 留言获得点赞的次数
$sql .= ",last_do_dislike_at int not null default 0"; // 最后一次对登记内容进行点赞的时间
$sql .= ",do_dislike_num int not null default 0"; // 对登记内容进行点赞的次数
$sql .= ",last_do_dislike_cowork_at int not null default 0"; // 最后一次对协作进行点赞的时间
$sql .= ",do_dislike_cowork_num int not null default 0"; // 对协作进行点赞的次数
$sql .= ",last_do_dislike_remark_at int not null default 0"; // 最后一次对留言进行点赞的时间
$sql .= ",do_dislike_remark_num int not null default 0"; // 对留言进行点赞的次数
$sql .= ",last_agree_at int not null default 0"; // 最后一次获得推荐的时间
$sql .= ",agree_num int not null default 0"; // 获得推荐的次数
$sql .= ",last_agree_cowork_at int not null default 0"; // 最后一次协作获得推荐的时间
$sql .= ",agree_cowork_num int not null default 0"; // 协作获得推荐的次数
$sql .= ",last_agree_remark_at int not null default 0"; // 最后一次留言获得推荐的时间
$sql .= ",agree_remark_num int not null default 0"; // 留言获得推荐的次数
$sql .= ",last_topic_at int not null default 0"; // 最后一次创建专题页的时间
$sql .= ",topic_num int not null default 0"; // 创建专题页的次数
$sql .= ",do_repos_read_num int not null default 0"; // 阅读共享页的次数
$sql .= ",do_repos_read_elapse int not null default 0"; // 阅读共享页的总时长
$sql .= ",do_topic_read_num int not null default 0"; // 阅读专题页的次数
$sql .= ",topic_read_num int not null default 0"; // 专题页被阅读的次数
$sql .= ",do_topic_read_elapse int not null default 0"; // 阅读专题页的时长
$sql .= ",topic_read_elapse int not null default 0"; // 专题页被阅读的总时长
$sql .= ",do_cowork_read_num int not null default 0"; // 阅读谈论页的次数
$sql .= ",cowork_read_num int not null default 0"; // 谈论页被阅读的次数
$sql .= ",do_cowork_read_elapse int not null default 0"; // 阅读谈论页的时长
$sql .= ",cowork_read_elapse int not null default 0"; //
$sql .= ",user_total_coin int not null default 0"; // 用户在活动中的轮次上的总积分
$sql .= ",score float default 0 COMMENT '得分'"; //
$sql .= ",state tinyint not null default 1"; //0:clean,1:normal,2:as invite log,100:后台删除,101:用户删除;
$sql .= ",modify_log longtext null"; // 数据修改日志
$sql .= ",custom text null"; // 用户自定义设置
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 记录活动页面记录追踪
 */
$sql = "create table if not exists xxt_enroll_trace(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",aid varchar(40) not null";
$sql .= ",rid varchar(13) not null default ''"; //
$sql .= ",page varchar(13) not null default ''"; //
$sql .= ",record_id int not null default 0"; //
$sql .= ",topic_id int not null default 0"; //
$sql .= ",userid varchar(40) not null";
$sql .= ",nickname varchar(255) not null default ''";
$sql .= ",event_first varchar(255) not null default ''";
$sql .= ",event_first_at int not null default 0";
$sql .= ",event_end varchar(255) not null default ''";
$sql .= ",event_end_at int not null default 0";
$sql .= ",event_elapse int not null default 0"; // 事件总时长
$sql .= ",events text null"; // 事件
$sql .= ",user_agent text null";
$sql .= ",client_ip varchar(40) not null default ''";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 填写记录的收藏记录
 */
$sql = "create table if not exists xxt_enroll_record_favor(";
$sql .= "id bigint not null auto_increment";
$sql .= ",aid varchar(40) not null";
$sql .= ",siteid varchar(32) not null default ''";
$sql .= ",record_id int not null"; // 填写记录的ID
$sql .= ",favor_unionid varchar(40) not null"; // 用户的注册账号ID
$sql .= ",favor_at int not null"; // 收藏填写的时间
$sql .= ",state tinyint not null default 1";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 活动登记记录主题
 */
$sql = "create table if not exists xxt_enroll_topic(";
$sql .= "id int not null auto_increment";
$sql .= ",aid varchar(40) not null";
$sql .= ",siteid varchar(32) not null";
$sql .= ",unionid varchar(40) not null default ''";
$sql .= ",userid varchar(40) not null";
$sql .= ",group_id varchar(32) not null default ''"; // 用户分组id
$sql .= ",nickname varchar(255) not null default ''";
$sql .= ",create_at int not null"; // 创建时间
$sql .= ",title varchar(255) not null default ''";
$sql .= ",summary varchar(240) not null default ''"; // 分享或生成链接时的摘要
$sql .= ",state tinyint not null default 1"; //0:clean,1:normal;
$sql .= ",rec_num int not null default 0";
$sql .= ",share_in_group char(1) not null default 'N'";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 活动登记记录主题与记录
 */
$sql = "create table if not exists xxt_enroll_topic_record(";
$sql .= "id bigint not null auto_increment";
$sql .= ",aid varchar(40) not null";
$sql .= ",siteid varchar(32) not null default ''";
$sql .= ",topic_id int not null";
$sql .= ",record_id int not null";
$sql .= ",assign_at int not null"; // 指定时间
$sql .= ",seq int not null default 0";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 记录活动标签
 */
$sql = 'create table if not exists xxt_enroll_tag(';
$sql .= 'id bigint not null auto_increment';
$sql .= ",siteid varchar(32) not null";
$sql .= ",aid varchar(40) not null";
$sql .= ',label varchar(255) not null';
$sql .= ",assign_num int not null default 0";
$sql .= ",user_num int not null default 0";
$sql .= ",public char(1) not null default 'N'";
$sql .= ",forbidden char(1) not null default 'N'";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 记录活动用户标签
 */
$sql = 'create table if not exists xxt_enroll_user_tag(';
$sql .= 'id bigint not null auto_increment';
$sql .= ",siteid varchar(32) not null";
$sql .= ",aid varchar(40) not null";
$sql .= ',tag_id bigint not null';
$sql .= ",userid varchar(40) not null";
$sql .= ',create_at int not null';
$sql .= ",state tinyint not null default 1"; // 事件是否有效
$sql .= ",assign_num int not null default 0";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 记录活动标签指定记录
 */
$sql = 'create table if not exists xxt_enroll_tag_assign(';
$sql .= 'id bigint not null auto_increment';
$sql .= ",siteid varchar(32) not null";
$sql .= ",aid varchar(40) not null";
$sql .= ',tag_id bigint not null';
$sql .= ',user_tag_id bigint not null';
$sql .= ",userid varchar(40) not null";
$sql .= ',assign_at int not null';
$sql .= ',target_id int not null'; // 被打标签的填写记录
$sql .= ',target_type tinyint not null default 1'; // 1:record
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 记录活动标签指定记录
 */
$sql = 'create table if not exists xxt_enroll_tag_target(';
$sql .= 'id bigint not null auto_increment';
$sql .= ",siteid varchar(32) not null";
$sql .= ",aid varchar(40) not null";
$sql .= ',tag_id bigint not null';
$sql .= ',first_assign_at int not null';
$sql .= ',last_assign_at int not null';
$sql .= ',target_id int not null'; // 被打标签的对象
$sql .= ',target_type tinyint not null default 1'; // 1:record
$sql .= ',assign_num int not null';
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
//
$sql = "create table if not exists xxt_enroll_log(";
$sql .= "id bigint not null auto_increment";
$sql .= ",aid varchar(40) not null";
$sql .= ",siteid varchar(32) not null default ''";
$sql .= ",rid varchar(13) not null default ''";
$sql .= ",enroll_key varchar(32) not null";
$sql .= ",group_id varchar(32) not null default ''";
$sql .= ",userid varchar(40) not null default ''"; // 发起操作的用户
$sql .= ",nickname varchar(255) not null default ''"; // 发起操作的用户昵称
$sql .= ",event_name varchar(255) not null default ''"; // 事件名称
$sql .= ",event_op varchar(10) not null default ''"; // 事件操作
$sql .= ",event_at int not null";
$sql .= ",target_id int not null"; // 事件操作的对象
$sql .= ",target_type varchar(20) not null"; // 事件操作的对象的类型
$sql .= ",earn_coin int not null default 0"; // 获得的积分奖励
$sql .= ",owner_userid varchar(40) not null default ''"; // 受到操作影响的用户
$sql .= ",owner_nickname varchar(255) not null default ''"; // 受到操作影响的用户昵称
$sql .= ",owner_earn_coin int not null default 0"; // 获得的积分奖励
$sql .= ",undo_event_id int not null default 0"; // 产生的结果是否已经被其他事件撤销
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
//
$sql = "create table if not exists xxt_enroll_notice(";
$sql .= "id bigint not null auto_increment";
$sql .= ",siteid varchar(32) not null default ''";
$sql .= ",aid varchar(40) not null";
$sql .= ",rid varchar(13) not null default ''";
$sql .= ",enroll_key varchar(32) not null";
$sql .= ",userid varchar(40) not null default ''"; // 被通知的用户
$sql .= ",nickname varchar(255) not null default ''"; // 被通知的用户的昵称
$sql .= ",notice_reason varchar(255) not null default ''"; // 被通知的原因
$sql .= ",event_userid varchar(40) not null default ''"; // 发起事件的用户
$sql .= ",event_nickname varchar(255) not null default ''"; // 发起事件的用户昵称
$sql .= ",event_target_id int not null"; // 事件操作的对象
$sql .= ",event_target_type varchar(20) not null"; // 事件操作的对象的类型
$sql .= ",event_name varchar(255) not null default ''"; // 事件名称
$sql .= ",event_op varchar(10) not null default ''"; // 事件操作
$sql .= ",event_at int not null";
$sql .= ",state tinyint not null default 1";
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
$sql .= ",enroll_app_id varchar(40) not null default ''"; // 关联的记录活动
$sql .= ",group_app_id varchar(40) not null default ''"; // 关联的分组活动
$sql .= ",creater varchar(40) not null default ''";
$sql .= ",creater_name varchar(255) not null default ''";
$sql .= ",create_at int not null";
$sql .= ",modifier varchar(40) not null default ''";
$sql .= ",modifier_name varchar(255) not null default ''";
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
$sql .= ",recycle_schemas longtext null"; // 放入回收站的定义
$sql .= ",assigned_nickname text"; // 填写题目中指定填写人昵称{"valid":"Y","schema":{"id":"xxxxxx"}}
$sql .= ",use_site_header char(1) not null default 'Y'"; // 使用站点页眉
$sql .= ",use_site_footer char(1) not null default 'Y'"; // 使用站点页脚
$sql .= ",use_mission_header char(1) not null default 'Y'"; // 使用项目页眉
$sql .= ",use_mission_footer char(1) not null default 'Y'"; // 使用项目页脚
$sql .= ",extattrs text"; //扩展属性
$sql .= ",tags text";
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
$sql .= ",create_at int not null";
$sql .= ",modifier varchar(40) not null default ''";
$sql .= ",modifier_name varchar(255) not null default ''";
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
$sql .= ",auto_sync char(1) not null default 'N'"; // 是否默认同步，
$sql .= ",sync_round varchar(32) not null default ''"; // 默认同步的组
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
$sql .= ",round_type char(1) not null default 'T'"; // 分组类型 T：团队，R：角色
$sql .= ",create_at int not null";
$sql .= ",title varchar(40) not null";
$sql .= ",autoplay char(1) not null default 'N'"; // 自动抽取直到达到抽取次数
$sql .= ",times int not null default 1"; // 抽取次数
$sql .= ",targets text null";
$sql .= ",extattrs text null"; //扩展属性
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
$sql .= ",is_leader char(1) not null default 'N'"; // 人员分组中的用户角色，N：组员，Y：组长，S：超级用户
$sql .= ",enroll_key varchar(32) not null";
$sql .= ",enroll_at int not null"; // 填写报名信息时间
$sql .= ",tags text null";
$sql .= ",comment text null";
$sql .= ",state tinyint not null default 1"; //0:remove,1:normal,2:as invite log
$sql .= ",referrer text null"; //
$sql .= ",round_id varchar(32) not null default ''"; // 团队分组
$sql .= ",round_title varchar(40) not null default ''";
$sql .= ",role_rounds varchar(255) not null default ''"; // 角色分组
$sql .= ",draw_at int not null"; // 加入人员分组的时间
$sql .= ",data text null"; // 登记的数据项
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