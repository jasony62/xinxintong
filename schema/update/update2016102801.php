<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "alter table xxt_shop_matter add push_home char(1) not null default 'N' after visible_scope";
$sqls[] = "alter table xxt_shop_matter add weight int not null default 0 after push_home";
$sqls[] = "alter table xxt_site add home_carousel text after home_page_name";
//
$sql = "create table if not exists xxt_home_matter (";
$sql .= "id int not null auto_increment";
$sql .= ",creater varchar(40) not null";
$sql .= ",creater_name varchar(255) not null default ''"; //from account or fans
$sql .= ",put_at int not null";
$sql .= ",siteid varchar(32) not null";
$sql .= ",matter_id varchar(40) not null";
$sql .= ",matter_type varchar(20) not null";
$sql .= ",scenario varchar(255) not null default ''"; // 登记／分组活动场景
$sql .= ",title varchar(70) not null default ''";
$sql .= ",pic text";
$sql .= ",summary varchar(240) not null";
$sql .= ",weight int not null default 0"; // 权重
$sql .= ",score int not null default 0"; // 评价
$sql .= ",approved char(1) not null default 'N'";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;
//
$sql = 'create table if not exists xxt_site_home_channel (';
$sql .= 'id int not null auto_increment';
$sql .= ",creater varchar(40) not null";
$sql .= ",creater_name varchar(255) not null default ''";
$sql .= ',put_at int not null';
$sql .= ",siteid varchar(32) not null";
$sql .= ",channel_id int not null";
$sql .= ",title varchar(70) not null default ''";
$sql .= ',pic text';
$sql .= ',summary varchar(240) not null';
$sql .= ",seq int not null default 0";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;
//
$sql = "create table if not exists xxt_home_site (";
$sql .= "id int not null auto_increment";
$sql .= ",creater varchar(40) not null";
$sql .= ",creater_name varchar(255) not null default ''";
$sql .= ",put_at int not null";
$sql .= ",siteid varchar(32) not null";
$sql .= ",title varchar(70) not null default ''";
$sql .= ",pic text";
$sql .= ",summary varchar(240) not null default ''";
$sql .= ",score int not null default 0";
$sql .= ",approved char(1) not null default 'N'"; // 是否批准推送到主页
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;
//
$sql = "create table if not exists xxt_platform(";
$sql .= "id int not null auto_increment";
$sql .= ",home_page_id int not null default 0"; // 平台主页
$sql .= ",home_page_name varchar(13) not null default ''"; // 平台主页
$sql .= ",home_carousel text"; // 首页轮播
$sql .= ",template_page_id int not null default 0"; // 平台模版库
$sql .= ",template_page_name varchar(13) not null default ''"; // 平台模版库
$sql .= ",site_page_id int not null default 0"; // 平台站点库
$sql .= ",site_page_name varchar(13) not null default ''"; // 平台站点库
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;