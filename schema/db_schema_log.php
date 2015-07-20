<?php
require_once '../db.php';
// log raw message.
$sql = "create table if not exists xxt_log(";
$sql .= 'id int not null auto_increment';
$sql .= ',mpid varchar(32) not null';
$sql .= ',create_at int(10) not null';
$sql .= ',method varchar(10) not null'; 
$sql .= ',data text';
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error(xxt_log): '.$mysqli->error;
}
/**
 * log received parsed msessages.
 * 这应该是一个全局日志，所有收到的用户消息都要记录
 */
$sql = "create table if not exists xxt_log_mpreceive(";
$sql .= 'id int not null auto_increment';
$sql .= ',mpid varchar(32) not null';
$sql .= ",msgid bigint(64) not null default 0";
$sql .= ',to_user varchar(255) not null';
$sql .= ',openid varchar(255) not null';
$sql .= ",nickname varchar(255) not null default ''";
$sql .= ',create_at int(10) not null';
$sql .= ',type varchar(10) not null'; // text,image,location,event
$sql .= ',data varchar(255) not null';
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error(xxt_log_mpreceive): '.$mysqli->error;
}
/**
 * log send parsed msessages.
 * 这应该是一个全局日志，所有发给用户的消息都要记录
 */
$sql = "create table if not exists xxt_log_mpsend(";
$sql .= 'id int not null auto_increment';
$sql .= ',mpid varchar(32) not null';
$sql .= ',openid varchar(255) not null';
$sql .= ',groupid varchar(255) not null';
$sql .= ',creater varchar(40) not null';
$sql .= ',create_at int(10) not null';
$sql .= ',content text';
$sql .= ',matter_type varchar(20)';
$sql .= ',matter_id varchar(40)';
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error(xxt_log_mpsend): '.$mysqli->error;
}
/**
 * 记录图文信息打开的情况
 */
$sql = "create table if not exists xxt_log_matter_read(";
$sql .= 'id int not null auto_increment';
$sql .= ',vid varchar(32) not null';
$sql .= ',ooid varchar(255) not null'; // OAuth openid
$sql .= ',read_at int not null';
$sql .= ',mpid varchar(32) not null';
$sql .= ',matter_id varchar(40) not null';
$sql .= ',matter_type varchar(20) not null'; //article,link
$sql .= ',matter_title varchar(70) not null'; 
$sql .= ',matter_shareby varchar(45)'; // 分享动作ID 
$sql .= ",user_agent text";
$sql .= ",client_ip varchar(40) not null default ''";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error(xxt_log_matter_read): '.$mysqli->error;
}
/**
 * 分享动作日志
 * 记录谁分享了什么，只能记录在打开页面中的分享行为 
 */
$sql = "create table if not exists xxt_log_matter_share(";
$sql .= 'id int not null auto_increment';
$sql .= ',shareid varchar(45)'; // 分享行为的主键 
$sql .= ',vid varchar(32) not null'; // 谁做的分享
$sql .= ',ooid varchar(255) not null'; // OAuth openid
$sql .= ',share_at int not null';
$sql .= ',share_to char(1)'; //朋友圈(T)或好友(F)
$sql .= ',mpid varchar(32) not null';
$sql .= ',matter_id varchar(40) not null';
$sql .= ',matter_type varchar(20) not null'; 
$sql .= ',matter_title varchar(70) not null'; 
$sql .= ',matter_shareby varchar(45)'; // 素材是通过谁分享获得的 
$sql .= ",user_agent text";
$sql .= ",client_ip varchar(40) not null default ''";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error(xxt_log_matter_share): '.$mysqli->error;
}
/**
 * 记录群发消息发送情况
 */
$sql = "create table if not exists xxt_log_massmsg(";
$sql .= 'id int not null auto_increment';
$sql .= ',mpid varchar(32) not null';
$sql .= ',matter_id varchar(40) not null';
$sql .= ',matter_type varchar(20) not null';
$sql .= ',sender varchar(40) not null';
$sql .= ',send_at int not null';
$sql .= ',message text'; // 发送的消息内容
$sql .= ',result varchar(255)'; // 发送的消息内容
$sql .= ',msgid bigint(64) not null'; 
$sql .= ',status varchar(255) not null';
$sql .= ',total_count int not null default 0';
$sql .= ',filter_count int not null default 0';
$sql .= ',sent_count int not null default 0';
$sql .= ',error_count int not null default 0';
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error(xxt_log_tmplmsg): '.$mysqli->error;
}
/**
 * 记录模板消息发送情况
 */
$sql = "create table if not exists xxt_log_tmplmsg(";
$sql .= 'id int not null auto_increment';
$sql .= ',mpid varchar(32) not null';
$sql .= ',template_id varchar(255) not null'; // 模板ID
$sql .= ',msgid varchar(50) not null'; // 消息ID
$sql .= ',openid varchar(255) not null';
$sql .= ',data text'; // 发送的消息内容
$sql .= ',create_at int not null';
$sql .= ',status varchar(255)'; // success|failed:user block|failed:system failed
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error(xxt_log_tmplmsg): '.$mysqli->error;
}
/**
 * 集中记录用户做了哪些动作，以便于进行统计分析
 * 
 * 阅读：xxt_log_matter_read
 * 分享：xxt_log_matter_share
 */
$sql = "create table if not exists xxt_log_user_action(";
$sql .= 'id int not null auto_increment';
$sql .= ',mpid varchar(32) not null';
$sql .= ',vid varchar(32) not null';
$sql .= ',openid varchar(255) not null';
$sql .= ',action_at int not null';
$sql .= ',act_read tinyint not null default 0'; 
$sql .= ',act_share_friend tinyint not null default 0'; 
$sql .= ',act_share_timeline tinyint not null default 0'; 
$sql .= ',original_logid int not null'; 
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: '.$mysqli->error;
}
/**
 * 集中记录围绕素材产生的日志，以便于进行统计分析
 */
$sql = "create table if not exists xxt_log_matter_action(";
$sql .= 'id int not null auto_increment';
$sql .= ',mpid varchar(32) not null';
$sql .= ',matter_id varchar(40) not null';
$sql .= ',matter_type varchar(20) not null';
$sql .= ',matter_title varchar(70) not null'; 
$sql .= ',action_at int not null';
$sql .= ',act_read tinyint not null default 0'; 
$sql .= ',act_share_friend tinyint not null default 0'; 
$sql .= ',act_share_timeline tinyint not null default 0'; 
$sql .= ',original_logid int not null'; 
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: '.$mysqli->error;
}

echo 'finish log.'.PHP_EOL;
