<?php
require_once '../../db.php';

$sqls = [];
/**
 * 支持的第三方登录
 */
$sql = "create table if not exists account_third (";
$sql .= "id varchar(40) not null";
$sql .= ",creator varchar(50) NOT NULL";
$sql .= ",creator_name varchar(100) not null default ''";
$sql .= ",create_at int NOT NULL";
$sql .= ",appname varchar(100) NOT NULL default ''"; //第三方名称
$sql .= ",app_short_name varchar(10) NOT NULL default ''"; //第三方名称
$sql .= ",pic text null"; // head image.
$sql .= ",appid varchar(100) NOT NULL default ''"; // 
$sql .= ",appsecret varchar(100) NOT NULL default ''"; //
$sql .= ",state tinyint not null default 1";
$sql .= ",primary key (id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;
/**
 * 第三方登录的用户
 */
$sql = "create table if not exists account_third_user (";
$sql .= "id int(11) unsigned NOT NULL AUTO_INCREMENT";
$sql .= ",third_id varchar(40) NOT NULL"; // 
$sql .= ",reg_time int(11) NOT NULL";
$sql .= ",openid varchar(100) not null";
$sql .= ",nickname varchar(255) not null default ''";
$sql .= ",headimgurl varchar(255) not null default ''";
$sql .= ",email varchar(255) not null default ''";
$sql .= ",moble varchar(20) not null default ''";
$sql .= ",sex tinyint not null default 0";
$sql .= ",city varchar(50) not null default ''";
$sql .= ",province varchar(50) not null default ''";
$sql .= ",country varchar(50) not null default ''";
$sql .= ",forbidden char(1) not null default 'N'";
$sql .= ",unionid varchar(32) not null default '' comment '用户的注册id'";
$sql .= ",primary key (id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;
//
$sqls[] = "ALTER TABLE  account_group add p_create_self_site tinyint not null default 0 comment '创建用户的默认团队'";
//
$sqls[] = "INSERT INTO account_group(group_id,group_name,asdefault,p_mpgroup_create,p_mp_create,p_mp_permission,p_platform_manage,p_create_self_site) VALUES(101,'dev189',0,0,0,0,0,0)";
//
// $sqls[] = "INSERT INTO account_third(id,creator,creator_name,create_at,appname,app_short_name,pic,appid,appsecret) VALUES('" . uniqid() . "','5771d91dcf713','aly'," . time() . ",'中国电信能力开放平台','dev189','/kcfinder/upload/c1aa4b1cb943c85ef98ca36db3d00620/图片/能力开放图标.jpg','20190403105121FZlTKz','86062df978b648afb903a2774cab443f')";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;