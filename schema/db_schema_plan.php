<?php
require_once '../../db.php';

$sqls = array();
/**
 * 计划任务活动
 */
$sql = "create table if not exists xxt_plan (";
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
$sql .= ",entry_rule text null"; // 参与规则
$sql .= ",check_schemas text null"; // 行动项的核对数据
$sql .= ",jump_delayed char(1) not null default 'Y'"; // Y:跳过；N:不跳过
$sql .= ",auto_verify char(1) not null default 'Y'";
$sql .= ",can_patch char(1) not null default 'Y'"; // 是否允许补填数据
$sql .= ",notweekend char(1) not null default 'N'"; // 周六周日是否生成任务
$sql .= ",rp_config text"; // 统计报告页面用户选择的标识信息
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;
/**
 * 计划任务定义
 */
$sql = "create table if not exists xxt_plan_task_schema (";
$sql .= "id bigint not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",aid varchar(40) not null";
$sql .= ",state tinyint not null default 1"; //0:删除,1:运行
$sql .= ",title varchar(255) not null default ''";
$sql .= ",task_seq int not null default 1"; // 任务执行顺序
$sql .= ",born_mode char(1) not null default 'U'"; // U(ser):用户指定时间；S(ubmit):用户首次提交时间；A(ssign):分配的固定时间；F(irst):第一个任务后间隔时间；P(revious):上一个任务间隔；G(uide)组织者给用户指定的时间
$sql .= ",born_offset varchar(20) not null default ''"; // 参见php DateInterval
$sql .= ",jump_delayed char(1) not null default 'U'"; // Y:跳过；N:不跳过；U:未指定
$sql .= ",auto_verify char(1) not null default 'U'";
$sql .= ",can_patch char(1) not null default 'U'"; // 是否允许补填数据
$sql .= ",as_placeholder char(1) not null default 'N'"; // 只占据时间，不需要执行任务
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;
/**
 * 计划任务行动定义
 */
$sql = "create table if not exists xxt_plan_action_schema (";
$sql .= "id bigint not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",aid varchar(40) not null";
$sql .= ",task_schema_id bigint not null"; // 所属任务定义
$sql .= ",state tinyint not null default 1"; //
$sql .= ",action_desc longtext null"; // 任务描述
$sql .= ",action_seq int not null default 1"; // 行动的顺序
$sql .= ",check_schemas text null"; // 行动项的核对数据
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;
/**
 * 执行的计划任务
 */
$sql = "create table if not exists xxt_plan_task (";
$sql .= "id bigint not null auto_increment";
$sql .= ",siteid varchar(32) not null default ''";
$sql .= ",aid varchar(40) not null";
$sql .= ",task_schema_id bigint not null"; // 所属任务定义
$sql .= ",task_seq int not null default 0"; // 任务执行顺序
$sql .= ",born_at int not null default 0"; // 计划生成时间
$sql .= ",patch_at int not null default 0"; // 实际生成时间
$sql .= ",group_id varchar(32) not null default ''"; // 用户分组id
$sql .= ",userid varchar(40) not null default ''";
$sql .= ",nickname varchar(255) not null default ''";
$sql .= ",headimgurl varchar(255) not null default ''";
$sql .= ",first_enroll_at int not null"; // 填写报名信息时间
$sql .= ",last_enroll_at int not null"; // 填写报名信息时间
$sql .= ",state tinyint not null default 1"; //0:remove,1:normal,2:as invite log,100:后台删除,101:用户删除;
$sql .= ",data longtext null"; // 登记的数据项
$sql .= ",supplement longtext null"; // 补充说明
$sql .= ",score text null"; // 打分题的得分记录
$sql .= ",comment text null";
$sql .= ",verified char(1) not null default 'P'"; // 记录是否已通过审核 Yes,No,Pending
$sql .= ",submit_log text null"; // 数据提交日志
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;
/**
 * 计划任务核对数据
 */
$sql = "create table if not exists xxt_plan_task_action (";
$sql .= "id bigint not null auto_increment";
$sql .= ",siteid varchar(32) not null default ''";
$sql .= ",aid varchar(40) not null";
$sql .= ",task_id bigint not null"; // 所属任务定义
$sql .= ",task_schema_id bigint not null"; // 所属任务定义
$sql .= ",action_schema_id bigint not null"; // 所属任务定义
$sql .= ",check_schema_id varchar(20) not null"; // 所属任务定义
$sql .= ",enroll_at int not null default 0"; // 数据的提交时间，和modify_log中的数据对应
$sql .= ",group_id varchar(32) not null default ''"; // 用户分组id
$sql .= ",userid varchar(40) not null default ''";
$sql .= ",value text null";
$sql .= ",supplement text null"; // 补充说明
$sql .= ",score float not null default 0";
$sql .= ",state tinyint not null default 1"; //0:remove,1:normal
$sql .= ",modify_log longtext null"; // 数据修改日志
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;
/**
 * 计划任务活动参与人
 */
$sql = "create table if not exists xxt_plan_user(";
$sql .= "id int not null auto_increment";
$sql .= ",siteid varchar(32) not null";
$sql .= ",aid varchar(40) not null";
$sql .= ",group_id varchar(32) not null default ''";
$sql .= ",userid varchar(40) not null default ''";
$sql .= ",nickname varchar(255) not null default ''";
$sql .= ",start_at int not null default 0"; // 开始执行第一个任务的时间
$sql .= ",task_num int not null default 0";
$sql .= ",last_enroll_at int not null default 0";
$sql .= ",score float not null default 0";
$sql .= ",coin int not null default 0";
$sql .= ",comment text null";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;
/**
 * 自定义登记数据统计
 */
$sql = "create table if not exists xxt_plan_task_stat(";
$sql .= "siteid varchar(32) not null";
$sql .= ",aid varchar(40) not null";
$sql .= ",task_schema_id int not null default 0";
$sql .= ",action_schema_id int not null default 0";
$sql .= ",create_at int not null";
$sql .= ",id varchar(40) not null";
$sql .= ",title varchar(255) not null";
$sql .= ",v varchar(40) not null";
$sql .= ",l varchar(255) not null";
$sql .= ",c double not null";
$sql .= ") ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;