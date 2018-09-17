<?php
require_once '../db.php';
$sqls = [];
/**
 * 平台注册账号
 */
$sql = "create table if not exists account (";
$sql .= "uid varchar(40) not null comment '用户的UID'";
$sql .= ",from_siteid varchar(32) not null default '' comment '从哪个团队发起的注册id'";
$sql .= ",authed_from varchar(20) default 'xxt' comment '哪个第三方应用'";
$sql .= ",authed_id varchar(255) default null comment '在第三方应用中的标识'";
$sql .= ",nickname varchar(50) default null";
$sql .= ",headimgurl varchar(255) not null default ''";
$sql .= ",email varchar(255) default null";
$sql .= ",password varchar(64) default null";
$sql .= ",salt varchar(32) default null";
$sql .= ",reg_time int default null comment '注册时间'";
$sql .= ",reg_ip varchar(128) default null comment '注册IP'";
$sql .= ",last_login int default 0 comment '最后登录时间'";
$sql .= ",last_ip varchar(128) default null comment '最后登录IP'";
$sql .= ",online_time int default 0 comment '在线时间(分钟)'";
$sql .= ",last_active int default null comment '最后活跃时间'";
$sql .= ",forbidden tinyint(3) default 0 comment '是否禁止用户'";
$sql .= ",is_first_login tinyint(1) default 1 comment '首次登录标记'";
$sql .= ",coin int not null default 0"; // 虚拟货币
$sql .= ",coin_last_at int not null default 0"; // 最近一次增加虚拟货币
$sql .= ",coin_day int not null default 0"; // 虚拟货币日增量
$sql .= ",coin_week int not null default 0"; // 虚拟货币周增量
$sql .= ",coin_month int not null default 0"; // 虚拟货币月增量
$sql .= ",coin_year int not null default 0"; // 虚拟货币年增量
$sql .= ",primary key (uid)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;
/**
 * 平台注册账号用户组
 */
$sql = "create table if not exists account_group (";
$sql .= "group_id int NOT NULL COMMENT '用户组的 ID'";
$sql .= ",group_name varchar(50) NOT NULL COMMENT '用户组名'";
$sql .= ",asdefault tinyint not null default 0 comment '作为缺省用户组'";
$sql .= ",p_mpgroup_create tinyint not null default 0 comment '创建公众号群'";
$sql .= ",p_mp_create tinyint not null default 0 comment '创建公众号'";
$sql .= ",p_mp_permission tinyint not null default 0 comment '设置公众号权限'";
$sql .= ",p_platform_manage tinyint  not null default 0 comment '平台管理'";
$sql .= ",p_create_site tinyint not null default 0 comment '创建团队'";
$sql .= ",view_name varchar(10) not null default 'default'";
$sql .= ",primary key (group_id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;
/**
 * 平台注册账号用户和用户组对应关系
 */
$sql = "create table if not exists account_in_group (";
$sql .= "account_uid varchar(40) NOT NULL COMMENT '用户的 ID'";
$sql .= ",group_id int NOT NULL COMMENT '用户组的 ID'";
$sql .= ",primary key (`account_uid`,`group_id`)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;
/**
 * 素材置顶表 make matter top
 */
$sql = "create table if not exists xxt_account_topmatter (";
$sql .= "id int(11) unsigned NOT NULL AUTO_INCREMENT";
$sql .= ",siteid varchar(32) NOT NULL";
$sql .= ",userid varchar(32) NOT NULL COMMENT '置顶操作的用户'";
$sql .= ",top enum('0','1') NOT NULL DEFAULT '0' COMMENT '置顶'";
$sql .= ",top_at int(11) NOT NULL COMMENT '置顶时间'";
$sql .= ",matter_id varchar(40) NOT NULL";
$sql .= ",matter_type varchar(20) NOT NULL";
$sql .= ",matter_title varchar(70) NOT NULL";
$sql .= ",primary key (id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;

/* 执行sql */
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo 'finish account.' . PHP_EOL;