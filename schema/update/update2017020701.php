<?php
require_once '../../db.php';

$sqls = array();
//
$sql = "create table if not exists xxt_site_registration (";
$sql .= "unionid varchar(32) not null comment '用户的注册id'";
$sql .= ",from_siteid varchar(32) not null comment '从哪个站点发起的注册id'";
$sql .= ",uname varchar(50) default null comment '登录用户名'";
$sql .= ",password varchar(64) default null comment '用户密码'";
$sql .= ",salt varchar(32) default null comment '用户附加混淆码'";
$sql .= ",nickname varchar(50) default null comment '用户昵称'";
$sql .= ",headimgurl varchar(255) not null default ''";
$sql .= ",email varchar(255) default null comment 'email'";
$sql .= ",mobile varchar(255) default null comment 'mobile'";
$sql .= ",reg_time int default null comment '注册时间'";
$sql .= ",reg_ip varchar(128) default null comment '注册ip'";
$sql .= ",last_login int default '0' comment '最后登录时间'";
$sql .= ",last_ip varchar(128) default null comment '最后登录ip'";
$sql .= ",last_active int default null comment '最后活跃时间'";
$sql .= ",forbidden tinyint(3) default '0' comment '是否禁止用户'";
$sql .= ",is_first_login char(1) default 'Y' comment '首次登录标记'";
$sql .= ",PRIMARY KEY (uid)";
$sql .= ") ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;
//
$sqls[] = "alter table xxt_site_account add unionid varchar(32) not null default '' comment '用户的注册id'";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;