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
$sql .= ",creater_src char(1) not null default 'A'"; // should remove
$sql .= ",create_at int not null";
$sql .= ",modifier varchar(40) not null default ''";
$sql .= ",modifier_name varchar(255) not null default ''";
$sql .= ",modifier_src char(1) not null default 'A'"; // should remove
$sql .= ',modify_at int not null';
$sql .= ",state tinyint not null default 1"; //0:stop,1:normal
$sql .= ",start_at int not null default 0"; // 开始时间
$sql .= ",end_at int not null default 0"; // 结束时间
$sql .= ",header_page_name varchar(13) not null default ''"; // 通用页头
$sql .= ",footer_page_name varchar(13) not null default ''"; // 通用页尾
$sql .= ",extattrs text null"; //扩展属性
$sql .= ",multi_phase char(1) not null default 'N'";
$sql .= ",user_app_id varchar(40) not null default ''"; // 项目的用户名单。项目中的登记活动，例如：报名活动。
$sql .= ",user_app_type varchar(10) not null default ''"; // 项目的用户名单应用的类型，例如：enroll，signin
$sql .= ",entry_rule text null"; // 参与规则
$sql .= ",matter_mg_tag varchar(255) not null default ''";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_mission): ' . $mysqli->error;
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
 * 登记活动的参与人及行为汇总，包含：登记人和评论人
 */
$sql = "create table if not exists xxt_mission_user(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",mission_id int not null";
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
$sql .= ",last_signin_at int not null default 0"; // 最后一次签到时间
$sql .= ",signin_num int not null default 0"; // 签到的次数
$sql .= ",user_total_coin int not null default 0"; // 用户在某个活动中的总分数
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
/**
 * 通知接收人
 */
$sql = "create table if not exists xxt_mission_receiver(";
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
 * 组成任务的素材
 */
$sql = "create table if not exists xxt_mission_matter(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",mission_id int not null";
$sql .= ",phase_id varchar(13) not null default ''";
$sql .= ",creater varchar(40) not null";
$sql .= ",creater_name varchar(255) not null default ''";
$sql .= ",creater_src char(1)";
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
/**
 * 任务的阶段
 */
$sql = "create table if not exists xxt_mission_phase(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",mission_id int not null";
$sql .= ",phase_id varchar(13) not null";
$sql .= ",title varchar(70) not null";
$sql .= ",state tinyint not null default 1"; //0:stop,1:normal
$sql .= ",start_at int not null default 0"; // 开始时间
$sql .= ",end_at int not null default 0"; // 结束时间
$sql .= ",summary varchar(240) not null";
$sql .= ",seq int not null default 0";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_mission_phase): ' . $mysqli->error;
}
/**
 * 项目报告配置信息
 */
$sql = "create table if not exists xxt_mission_report(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",mission_id int not null";
$sql .= ",creater varchar(40) not null default ''";
$sql .= ",creater_name varchar(255) not null default ''";
$sql .= ",create_at int not null";
$sql .= ",as_default char(1) not null default 'Y'";
$sql .= ",include_apps text"; // 报告中包含的应用的数组
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_mission_matter): ' . $mysqli->error;
}

echo 'finish xxt_mission.' . PHP_EOL;