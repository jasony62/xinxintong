<?php
require_once '../db.php';
/**
 * 文本事件响应映射关系
 */ 
$sql = "create table if not exists xxt_text_call_reply(";
$sql .= 'id int not null auto_increment';
$sql .= ',mpid varchar(32) not null';
$sql .= ',keyword varchar(100) not null';
$sql .= ',match_mode varchar(10) not null default "full"';
$sql .= ',matter_type varchar(14) not null'; // Text,Article,News
$sql .= ",matter_id varchar(128) default ''";
$sql .= ",access_control char(1) not null default 'N'";
$sql .= ",authapis text"; // 限定访问控制的认证接口
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!mysql_query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: '.mysql_error();
}
/**
 * 菜单回复设置
 */
$sql = "create table if not exists xxt_menu_reply(";
$sql .= 'mpid varchar(32) not null';
$sql .= ',version int not null default 0';
$sql .= ",published char(1) not null default 'N'"; //0:editing|1:published
$sql .= ',menu_key varchar(128) not null';
$sql .= ',pversion int not null default -1';
$sql .= ',creater varchar(40) not null';
$sql .= ',create_at int not null';
$sql .= ',menu_name varchar(40) not null';
$sql .= ',l1_pos tinyint not null default 0';
$sql .= ',l2_pos tinyint not null default 0';
$sql .= ",url varchar(256) default ''"; 
$sql .= ",matter_type varchar(14) default ''"; // Text,Article,News
$sql .= ",matter_id varchar(128) default ''";
$sql .= ",asview char(1) not null default 'N'";
$sql .= ",access_control char(1) not null default 'N'";
$sql .= ",authapis text"; // 限定访问控制的认证接口
$sql .= ",primary key(mpid,version,menu_key)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!mysql_query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: '.mysql_error();
}
/**
 * 二维码信息
 */ 
$sql = "create table if not exists xxt_qrcode_call_reply(";
$sql .= 'id int not null auto_increment';
$sql .= ',mpid varchar(32) not null';
$sql .= ',src char(2) not null';
$sql .= ',scene_id int not null';
$sql .= ',expire_at int not null default 0';
$sql .= ',name varchar(50) not null';
$sql .= ',pic text';
$sql .= ',matter_type varchar(14)';
$sql .= ',matter_id varchar(128)';
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!mysql_query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: '.mysql_error();
}
/**
 * 固定回复信息
 */ 
$sql = "create table if not exists xxt_other_call_reply(";
$sql .= 'id int not null auto_increment';
$sql .= ',mpid varchar(32) not null';
$sql .= ',name varchar(50) not null'; // subscribe/universal
$sql .= ',title varchar(50) not null'; // 关注/缺省
$sql .= ',matter_type varchar(14)'; // Text,Article,News
$sql .= ",matter_id varchar(128) default ''";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!mysql_query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: '.mysql_error();
}
/**
 * 回复访问控制列表
 */
$sql = "create table if not exists xxt_call_acl(";
$sql .= 'id int not null auto_increment';
$sql .= ',mpid varchar(32) not null';
$sql .= ',call_type varchar(10) not null';
$sql .= ',keyword varchar(100) not null';
$sql .= ',identity varchar(100) not null'; 
$sql .= ",idsrc char(2) not null default ''"; 
$sql .= ",label varchar(255) not null default ''"; 
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!mysql_query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: '.mysql_error();
}
echo 'finish reply.'.PHP_EOL;
