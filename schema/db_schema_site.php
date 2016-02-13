<?php
require_once '../db.php';
/**
 * site
 */
$sql = "create table if not exists xxt_site(";
$sql .= 'id varchar(32) not null';
$sql .= ',name varchar(50) not null';
$sql .= ",heading_pic text"; // 缺省头图
$sql .= ',creater varchar(40) not null';
$sql .= ",creater_name varchar(255) not null default ''";
$sql .= ',create_at int not null';
$sql .= ",asparent char(1) not null default 'N'"; // 是否作为父站点
$sql .= ",site_id varchar(32) not null default ''"; // 父站点ID
$sql .= ',state tinyint not null default 1'; // 1:正常, 0:停用
$sql .= ',home_page_id int not null default 0'; // 站点主页
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_site): ' . $mysqli->error;
}
/**
 * 站点授权管理员
 */
$sql = "create table if not exists xxt_site_admin(";
$sql .= "site_id varchar(32) not null";
$sql .= ",uid varchar(40) not null";
$sql .= ",creater varchar(40) not null";
$sql .= ",creater_name varchar(255) not null default ''";
$sql .= ',create_at int not null';
$sql .= ",primary key(site_id,uid)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_mppermission): ' . $mysqli->error;
}
/**
 * user account
 */
$sql = "CREATE TABLE IF NOT EXISTS xxt_site_account (";
$sql .= "site_id varchar(32) not null COMMENT '站点ID'";
$sql .= ",uid varchar(40) NOT NULL COMMENT '用户的ID'";
$sql .= ",uname varchar(50) DEFAULT NULL COMMENT '登录用户名'";
$sql .= ",password varchar(64) DEFAULT NULL COMMENT '用户密码'";
$sql .= ",salt varchar(32) DEFAULT NULL COMMENT '用户附加混淆码'";
$sql .= ",nickname varchar(50) DEFAULT NULL COMMENT '用户昵称'";
$sql .= ",headimgurl varchar(255) not null default ''";
$sql .= ",email varchar(255) DEFAULT NULL COMMENT 'EMAIL'";
$sql .= ",mobile varchar(255) DEFAULT NULL COMMENT 'MOBILE'";
$sql .= ",reg_time int DEFAULT NULL COMMENT '注册时间'";
$sql .= ",reg_ip varchar(128) DEFAULT NULL COMMENT '注册IP'";
$sql .= ",last_login int DEFAULT '0' COMMENT '最后登录时间'";
$sql .= ",last_ip varchar(128) DEFAULT NULL COMMENT '最后登录 IP'";
$sql .= ",last_active int DEFAULT NULL COMMENT '最后活跃时间'";
$sql .= ",forbidden tinyint(3) DEFAULT '0' COMMENT '是否禁止用户'";
$sql .= ",is_first_login tinyint(1) DEFAULT '1' COMMENT '首次登录标记'";
$sql .= ",level_id int DEFAULT NULL COMMENT '用户级别'";
$sql .= ",read_num int not null default 0"; // 累积阅读数
$sql .= ",share_friend_num int not null default 0"; // 累积分享给好友数
$sql .= ",share_timeline_num int not null default 0"; // 累积分享朋友圈数
$sql .= ",favor_num int not null default 0"; //收藏的数量
$sql .= ",coin int not null"; // 虚拟货币
$sql .= ",coin_last_at int not null"; // 最近一次增加虚拟货币
$sql .= ",coin_day int not null"; // 虚拟货币日增量
$sql .= ",coin_week int not null"; // 虚拟货币周增量
$sql .= ",coin_month int not null"; // 虚拟货币月增量
$sql .= ",coin_year int not null"; // 虚拟货币年增量
$sql .= ",PRIMARY KEY (uid)";
$sql .= ") ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_site_account): ' . $mysqli->error;
}
/**
 * user favor
 */
$sql = "create table if not exists xxt_site_favor(";
$sql .= "id int not null auto_increment";
$sql .= ",site_id varchar(32) not null";
$sql .= ",userid varchar(32) not null";
$sql .= ",nickname varchar(50)";
$sql .= ",favor_at int not null";
$sql .= ",matter_id varchar(40) not null";
$sql .= ",matter_type varchar(20) not null";
$sql .= ",matter_title varchar(70) not null";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
	header('HTTP/1.0 500 Internal Server Error');
	echo 'database error(xxt_log_matter_read): ' . $mysqli->error;
}
echo 'finish site_account.' . PHP_EOL;