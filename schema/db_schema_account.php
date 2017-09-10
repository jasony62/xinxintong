<?php
require_once '../db.php';
// platform account
$sql = "create table if not exists account (";
$sql .= "uid varchar(40) not null comment '用户的UID'";
$sql .= "from_siteid varchar(32) not null default '' comment '从哪个团队发起的注册id'";
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
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
// account group and group's permissions.
$sql = 'CREATE TABLE IF NOT EXISTS `account_group` (
    `group_id` int NOT NULL COMMENT \'用户组的 ID\',
    `group_name` varchar(50) NOT NULL COMMENT \'用户组名\',
    `asdefault` tinyint not null default 0 comment \'作为缺省用户组\',
    `p_mpgroup_create` tinyint not null default 0 comment \'创建公众号群\',
    `p_mp_create` tinyint not null default 0 comment \'创建公众号\',
    `p_mp_permission` tinyint not null default 0 comment \'设置公众号权限\',
    `p_platform_manage` tinyint  not null default 0 comment \'平台管理\',
    PRIMARY KEY (`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8';
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}
// relation of acount and group.
$sql = 'CREATE TABLE IF NOT EXISTS `account_in_group` (
    `account_uid` varchar(40) NOT NULL COMMENT \'用户的 ID\',
    `group_id` int NOT NULL COMMENT \'用户组的 ID\',
    PRIMARY KEY (`account_uid`,`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8';
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error: ' . $mysqli->error;
}

//素材置顶表 make matter top
$sqls[] = "CREATE TABLE `xxt_account_topmatter` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `siteid` varchar(32) NOT NULL,
  `userid` varchar(32) NOT NULL COMMENT '置顶操作的用户',
  `top` enum('0','1') NOT NULL DEFAULT '0' COMMENT '置顶',
  `top_at` int(11) NOT NULL COMMENT '置顶时间',
  `matter_id` varchar(40) NOT NULL,
  `matter_type` varchar(20) NOT NULL,
  `matter_title` varchar(70) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='素材置顶表';
";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo 'finish account.' . PHP_EOL;