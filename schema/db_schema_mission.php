<?php
require_once '../db.php';
/**
 * 项目、运营任务，素材和应用的集合
 */
$sql = "create table if not exists xxt_mission (";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",title varchar(70) not null";
$sql .= ",summary varchar(240) not null default ''";
$sql .= ",pic text null";
$sql .= ",creater varchar(40) not null default ''";
$sql .= ",creater_name varchar(255) not null default ''";
$sql .= ",create_at int not null";
$sql .= ",modifier varchar(40) not null default ''";
$sql .= ",modifier_name varchar(255) not null default ''";
$sql .= ',modify_at int not null';
$sql .= ",state tinyint not null default 1"; //0:stop,1:normal
$sql .= ",start_at int not null default 0"; // 开始时间
$sql .= ",end_at int not null default 0"; // 结束时间
$sql .= ",header_page_name varchar(13) not null default ''"; // 通用页头
$sql .= ",footer_page_name varchar(13) not null default ''"; // 通用页尾
$sql .= ",extattrs text null"; //扩展属性
$sql .= ",user_app_id varchar(40) not null default ''"; // 项目的用户名单。项目中的记录活动，例如：报名活动。
$sql .= ",user_app_type varchar(10) not null default ''"; // 项目的用户名单应用的类型，例如：enroll，signin
$sql .= ",entry_rule text null"; // 参与规则
$sql .= ",round_cron text null"; // 定时创建轮次规则
$sql .= ",matter_mg_tag varchar(255) not null default ''";
$sql .= ",wxacode_url text null"; // 参与规则
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_mission): ' . $mysqli->error;
}
/**
 * 项目的轮次
 */
$sql = "create table if not exists xxt_mission_round(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",rid varchar(13) not null";
$sql .= ",mission_id int not null";
$sql .= ",creator varchar(40) not null default ''";
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
 * 运营任务访问控制列表，记录任务的所有访问关系
 * 角色：
 * 所有者（Owner），创建任务的人
 * 管理员（Admin），创建人所在站点的管理员
 * 合作者（Coworker），指定的任务合作人
 */
$sql = "create table if not exists xxt_mission_acl (";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null"; // 任务所属的站点
$sql .= ",mission_id int not null";
$sql .= ",title varchar(70) not null"; // 任务的标题
$sql .= ",summary varchar(240) not null"; // 任务摘要
$sql .= ",pic text"; // 任务图片
$sql .= ",creater varchar(40) not null default ''"; // 任务的创建者
$sql .= ",creater_name varchar(255) not null default ''";
$sql .= ",create_at int not null"; // 任务的创建时间
$sql .= ",inviter varchar(40) not null default ''"; // 邀请人
$sql .= ",inviter_label varchar(255) not null default ''";
$sql .= ",invite_at int not null"; // 邀请时间
$sql .= ",coworker varchar(40) not null default ''"; // 合作者
$sql .= ",coworker_label varchar(255) not null default ''";
$sql .= ",coworker_role char(1) not null default 'C'"; // 合作者角色：Owner，Admin，Coworker
$sql .= ",join_at int not null"; // 加入时间
$sql .= ",last_invite char(1) not null default 'N'"; // 是否为最近一次邀请者对合作者发出的邀请，为了记录邀请关系
$sql .= ",state tinyint not null default 1"; //0:stop,1:normal
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_mission): ' . $mysqli->error;
}
/**
 * 记录活动的参与人及行为汇总，包含：登记人和留言人
 */
$sql = "create table if not exists xxt_mission_user(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",mission_id int not null";
$sql .= ",group_id varchar(32) not null default ''"; // 用户分组id
$sql .= ",userid varchar(40) not null default ''";
$sql .= ",nickname varchar(255) not null default ''";
$sql .= ",entry_num int not null default 0"; // 进入活动的次数
$sql .= ",last_entry_at int not null default 0"; // 最后一次进入时间
$sql .= ",total_elapse int not null default 0"; // 参与活动的总时长
$sql .= ",last_enroll_at int not null default 0"; // 最后一次登记时间
$sql .= ",enroll_num int not null default 0"; // 登记记录的条数
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
$sql .= ",last_dislike_at int not null default 0"; // 登记内容最后一次获得点赞的时间
$sql .= ",dislike_num int not null default 0"; // 登记内容获得点赞的次数
$sql .= ",last_dislike_cowork_at int not null default 0"; // 协作填写最后一次获得点赞的时间
$sql .= ",dislike_cowork_num int not null default 0"; // 协作填写获得点赞的次数
$sql .= ",last_dislike_remark_at int not null default 0"; // 留言最后一次获得点赞的时间
$sql .= ",dislike_remark_num int not null default 0"; // 留言获得点赞的次数
$sql .= ",last_do_remark_at int not null default 0"; // 最后一次发表评价的时间
$sql .= ",do_remark_num int not null default 0"; // 发表的评价条数
$sql .= ",last_do_like_at int not null default 0"; // 最后一次对登记内容进行点赞的时间
$sql .= ",do_like_num int not null default 0"; // 对登记内容进行点赞的次数
$sql .= ",last_do_like_cowork_at int not null default 0"; // 最后一次对协作进行点赞的时间
$sql .= ",do_like_cowork_num int not null default 0"; // 对协作进行点赞的次数
$sql .= ",last_do_like_remark_at int not null default 0"; // 最后一次对留言进行点赞的时间
$sql .= ",do_like_remark_num int not null default 0"; // 对留言进行点赞的次数
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
$sql .= ",last_signin_at int not null default 0"; // 最后一次签到时间
$sql .= ",signin_num int not null default 0"; // 签到的次数
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
$sql .= ",do_rank_read_num int not null default 0"; // 阅读排行榜的次数
$sql .= ",do_rank_read_elapse int not null default 0"; // 阅读排行榜的总时长
$sql .= ",user_total_coin int not null default 0"; // 用户的总积分
$sql .= ",score float not null default 0"; // 用户总得分
$sql .= ",state tinyint not null default 1"; //0:clean,1:normal,100:后台删除,101:用户删除;
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
$sql = "create table if not exists xxt_mission_trace(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",mission_id int not null";
$sql .= ",page varchar(13) not null default ''"; //
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
 * 组成任务的素材
 */
$sql = "create table if not exists xxt_mission_matter(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",mission_id int not null";
$sql .= ",creater varchar(40) not null";
$sql .= ",creater_name varchar(255) not null default ''";
$sql .= ",create_at int not null";
$sql .= ",matter_id varchar(40) not null";
$sql .= ",matter_type varchar(20)";
$sql .= ",matter_title varchar(70) not null";
$sql .= ",scenario varchar(255) not null default ''";
$sql .= ",start_at int not null default 0";
$sql .= ",end_at int not null default 0";
$sql .= ",is_public char(1) not null default 'Y'"; // 是否为公开素材
$sql .= ",seq int not null default 65535"; // 素材在项目中的排列顺序
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_mission_matter): ' . $mysqli->error;
}

echo 'finish xxt_mission.' . PHP_EOL;