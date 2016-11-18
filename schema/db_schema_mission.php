<?php
require_once '../db.php';
/**
 * 项目、运营任务，素材和应用的集合
 */
$sql = "create table if not exists xxt_mission (";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",mpid varchar(32) not null default ''"; // should remove
$sql .= ",title varchar(70) not null";
$sql .= ",summary varchar(240) not null";
$sql .= ",pic text";
$sql .= ",creater varchar(40) not null default ''";
$sql .= ",creater_name varchar(255) not null default ''";
$sql .= ",creater_src char(1)"; // should remove
$sql .= ",create_at int not null";
$sql .= ",modifier varchar(40) not null default ''";
$sql .= ",modifier_name varchar(255) not null default ''";
$sql .= ",modifier_src char(1)"; // should remove
$sql .= ',modify_at int not null';
$sql .= ",state tinyint not null default 1"; //0:stop,1:normal
$sql .= ",access_control char(1) not null default 'N'";
$sql .= ",authapis text";
$sql .= ",start_at int not null default 0"; // 开始时间
$sql .= ",end_at int not null default 0"; // 结束时间
$sql .= ",header_page_name varchar(13) not null default ''"; // 通用页头
$sql .= ",footer_page_name varchar(13) not null default ''"; // 通用页尾
$sql .= ",extattrs text"; //扩展属性
$sql .= ",multi_phase char(1) not null default 'N'";
$sql .= ",user_app_id varchar(40) not null default ''"; // 项目的用户名单。项目中的登记活动，例如：报名活动。
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
$sql .= ",state tinyint not null default 1"; //0:stop,1:normal
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_mission): ' . $mysqli->error;
}
/**
 * 组成任务的素材
 */
$sql = "create table if not exists xxt_mission_matter(";
$sql .= "mpid varchar(32) not null default ''";
$sql .= ",siteid varchar(32) not null";
$sql .= ",mission_id int not null";
$sql .= ",phase_id varchar(13) not null default ''";
$sql .= ",creater varchar(40) not null";
$sql .= ",creater_name varchar(255) not null default ''";
$sql .= ",creater_src char(1)";
$sql .= ",create_at int not null";
$sql .= ",matter_id varchar(40) not null";
$sql .= ",matter_type varchar(20)";
$sql .= ",seq int not null default 0";
$sql .= ",primary key(mission_id,matter_id,matter_type)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
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
 * 参与任务的用户
 * 用户清单来源于“xxt_mission.user_app_id”指定的登记活动
 */
$sql = "create table if not exists xxt_mission_user(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",mission_id int not null";
$sql .= ",userid varchar(40) not null default ''";
$sql .= ",nickname varchar(255) not null default ''";
$sql .= ",first_act_at int not null default 0"; // 首次操作时间
$sql .= ",last_act_at int not null default 0"; // 最后一次操作时间
$sql .= ",enroll_app text"; // 登记应用活动记录
$sql .= ",signin_app text"; // 签到应用活动记录
$sql .= ",group_app text"; // 分组应用活动记录
$sql .= ",assoc_enroll_app text"; // 作为关联数据登记应用活动记录
$sql .= ",assoc_group_app text"; // 作为关联数据登记应用活动记录
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_mission_matter): ' . $mysqli->error;
}

echo 'finish xxt_mission.' . PHP_EOL;