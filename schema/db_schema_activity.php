<?php
require_once '../db.php';
/*
 * 通用活动
 */
$sql = 'create table if not exists xxt_activity(';
$sql .= 'aid varchar(40) not null';
$sql .= ',mpid varchar(32) not null';
$sql .= ",creater varchar(40) not null default ''";
$sql .= ",promoter varchar(255) not null default ''"; //openid
$sql .= ",src char(2) not null default ''"; //openid'src
$sql .= ",create_at int not null";
$sql .= ",deleted char(1) not null default 'N'";
$sql .= ',code varchar(6) not null'; //活动的编码
$sql .= ",title varchar(255) not null default ''";
$sql .= ',pic text'; // 分享或生成链接时的图片 
$sql .= ',summary varchar(240) not null'; // 分享或生成链接时的摘要
$sql .= ",start_at int not null default 0"; // 开始时间
$sql .= ",end_at int not null default 0"; // 结束时间
$sql .= ",wxyx_only char(1) not null default 'N'"; // 禁止通过浏览器打开
$sql .= ",fans_only char(1) not null default 'N'";
$sql .= ",fans_enter_only char(1) not null default 'N'"; //仅限关注用户进入
$sql .= ",access_control char(1) not null default 'N'";
$sql .= ",authapis text";
$sql .= ',success_matter_type varchar(14)';
$sql .= ',success_matter_id varchar(128)';
$sql .= ',failure_matter_type varchar(14)';
$sql .= ',failure_matter_id varchar(128)';
$sql .= ",entry_page varchar(20) not null default 'form'";
$sql .= ",enrolled_entry_page varchar(20) not null default ''";
$sql .= ",receiver_page varchar(20) not null";
$sql .= ',form_code_id int not null default 0'; // 表单页
$sql .= ',nonfans_alert text'; //非关注用户提示
$sql .= ",open_lastroll char(1) not null default 'Y'"; // 打开最后一条登记记录，还是编辑新的
$sql .= ',result_code_id int not null default 0'; // 结果页
$sql .= ",can_signin char(1) not null default 'N'"; // 是否支持签到
$sql .= ",can_lottery char(1) not null default 'N'"; // 是否支持抽奖 
$sql .= ",tags text";
$sql .= ',primary key(aid)) ENGINE=MyISAM DEFAULT CHARSET=utf8';
if (!mysql_query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: '.mysql_error();
}
/**
 * 活动页面
 */
$sql = 'create table if not exists xxt_activity_page(';
$sql .= 'id int not null auto_increment';
$sql .= ',mpid varchar(32) not null';
$sql .= ',aid varchar(40) not null';
$sql .= ",creater varchar(40) not null default ''";
$sql .= ",create_at int not null";
$sql .= ",type char(1) not null default 'V'";
$sql .= ",title varchar(70) not null default ''";
$sql .= ",name varchar(20) not null default ''";
$sql .= ',code_id int not null default 0'; // from xxt_code_page 
$sql .= ',primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8';
if (!mysql_query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: '.mysql_error();
}
/**
 * 活动轮次
 */
$sql = 'create table if not exists xxt_activity_round(';
$sql .= 'id int not null auto_increment';
$sql .= ',rid varchar(13) not null';
$sql .= ',aid varchar(40) not null';
$sql .= ',mpid varchar(32) not null';
$sql .= ",creater varchar(40) not null default ''";
$sql .= ",create_at int not null";
$sql .= ",start_at int not null"; // 轮次开始时间 
$sql .= ",end_at int not null"; // 轮次结束时间
$sql .= ",title varchar(70) not null default ''"; // 分享或生成链接时的标题
$sql .= ',summary varchar(240)'; // 分享或生成链接时的摘要
$sql .= ",state tinyint not null default 0"; // 0:新建|1:启用|2:停用
$sql .= ',primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8';
if (!mysql_query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: '.mysql_error();
}
/**
 * 登记信息通知接收人 
 */
$sql = "create table if not exists xxt_activity_receiver(";
$sql .= 'id int not null auto_increment';
$sql .= ',mpid varchar(32) not null';
$sql .= ',aid varchar(40) not null';
$sql .= ',identity varchar(100) not null'; 
$sql .= ",idsrc char(2) not null default ''"; 
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!mysql_query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: '.mysql_error();
}
/**
 * 活动登记评分
 * enroll_key mpid+uniqid+name 
 */
$sql = 'create table if not exists xxt_activity_enroll(';
$sql .= 'id int not null auto_increment';
$sql .= ',aid varchar(40) not null';
$sql .= ',mpid varchar(32) not null';
$sql .= ',rid varchar(13) not null';
$sql .= ",src char(2) not null default ''";
$sql .= ",openid varchar(255) not null default ''";
$sql .= ",enroll_key varchar(32) not null";
$sql .= ",enroll_at int not null"; // 填写报名信息时间
$sql .= ",signin_at int not null"; // 签到时间
$sql .= ",tags text";
$sql .= ",comment text";
$sql .= ',vid varchar(32)';
$sql .= ',mid varchar(32)';
$sql .= ',score int';
$sql .= ',primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8';
if (!mysql_query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: '.mysql_error();
}
/**
 * 活动登记评分 
 */
$sql = "create table if not exists xxt_activity_enroll_score(";
$sql .= 'id int not null auto_increment';
$sql .= ",enroll_key varchar(32) not null";
$sql .= ",openid varchar(255)";
$sql .= ',create_at int not null';
$sql .= ',score int not null default 0';
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!mysql_query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: '.mysql_error();
}
/**
 * 活动登记评论
 */
$sql = "create table if not exists xxt_activity_enroll_remark(";
$sql .= 'id int not null auto_increment';
$sql .= ",enroll_key varchar(32) not null";
$sql .= ",openid varchar(255)";
$sql .= ',create_at int';
$sql .= ',remark text';
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!mysql_query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: '.mysql_error();
}
/**
 * 活动报名自定义信息
 */
$sql = 'create table if not exists xxt_activity_enroll_cusdata(';
$sql .= 'aid varchar(40) not null';
$sql .= ",enroll_key varchar(32) not null";
$sql .= ',name varchar(40) not null';
$sql .= ',value text';
$sql .= ',primary key(aid,enroll_key,name)) ENGINE=MyISAM DEFAULT CHARSET=utf8';
if (!mysql_query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: '.mysql_error();
}
/*
 * 活动访问列表 
 */
$sql = 'create table if not exists xxt_act_acl(';
$sql .= 'id int not null auto_increment';
$sql .= ',mpid varchar(32) not null';
$sql .= ',act_type char(1) not null';
$sql .= ',act_id varchar(32) not null';
$sql .= ',identity varchar(100) not null'; 
$sql .= ",idsrc char(2) not null default ''"; 
$sql .= ",label varchar(255) not null default ''"; 
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!mysql_query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: '.mysql_error();
}

echo 'finish activity.'.PHP_EOL;
