<?php
require_once '../db.php';
/*
 * 通用活动
 */
$sql = 'create table if not exists xxt_enroll(';
$sql .= 'id varchar(40) not null';
$sql .= ',mpid varchar(32) not null';
$sql .= ",creater varchar(40) not null default ''";
$sql .= ",creater_name varchar(255) not null default ''"; //from account or fans
$sql .= ",creater_src char(1)"; //A:accouont|F:fans
$sql .= ",create_at int not null";
$sql .= ",public_visible char(1) not null default 'N'";
$sql .= ",shift2pc char(1) not null default 'N'"; // 
$sql .= ",can_taskcode char(1) not null default 'N'";
$sql .= ',state tinyint not null default 1'; //0:stop,1:normal
$sql .= ",title varchar(255) not null default ''";
$sql .= ',pic text'; // 分享或生成链接时的图片 
$sql .= ',summary varchar(240) not null'; // 分享或生成链接时的摘要
$sql .= ",start_at int not null default 0"; // 开始时间
$sql .= ",before_start_page varchar(20) not null default ''";
$sql .= ",end_at int not null default 0"; // 结束时间
$sql .= ",after_end_page varchar(20) not null default ''";
$sql .= ",access_control char(1) not null default 'N'";
$sql .= ",authapis text";
$sql .= ",entry_rule text"; // 参与规则
$sql .= ',success_matter_type varchar(14)';
$sql .= ',success_matter_id varchar(128)';
$sql .= ',failure_matter_type varchar(14)';
$sql .= ',failure_matter_id varchar(128)';
$sql .= ",enrolled_entry_page varchar(20) not null default ''";
$sql .= ",receiver_page varchar(20) not null";
$sql .= ",remark_notice_page varchar(20) not null";
$sql .= ',form_code_id int not null default 0'; // 表单页
$sql .= ",open_lastroll char(1) not null default 'Y'"; // 打开最后一条登记记录，还是编辑新的
$sql .= ",multi_rounds char(1) not null default 'Y"; // 支持轮次
$sql .= ",can_like_record char(1) not null default 'N'"; // 支持对登记记录点赞
$sql .= ",can_remark_record char(1) not null default 'N'"; // 支持对登记记录评论
$sql .= ",can_signin char(1) not null default 'N'"; // 是否支持签到
$sql .= ",can_lottery char(1) not null default 'N'"; // 是否支持抽奖 
$sql .= ",remark_notice char(1) not null default 'N'";
$sql .= ",tags text";
$sql .= ',primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8';
if (!$mysqli->query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: '.$mysqli->error;
}
/**
 * 活动页面
 */
$sql = 'create table if not exists xxt_enroll_page(';
$sql .= 'id int not null auto_increment';
$sql .= ',mpid varchar(32) not null';
$sql .= ',aid varchar(40) not null';
$sql .= ",creater varchar(40) not null default ''";
$sql .= ",create_at int not null";
$sql .= ",type char(1) not null default 'V'"; //I:input,V:view
$sql .= ",title varchar(70) not null default ''";
$sql .= ",name varchar(20) not null default ''";
$sql .= ',code_id int not null default 0'; // from xxt_code_page 
$sql .= ",share_page char(1) default 'N'"; // 分享时分享当前页还是分享活动，缺省分享活动
$sql .= ",share_summary varchar(240)"; // 分享时的摘要字段
$sql .= ',primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8';
if (!$mysqli->query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: '.$mysqli->error;
}
/**
 * 活动轮次
 */
$sql = 'create table if not exists xxt_enroll_round(';
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
if (!$mysqli->query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: '.$mysqli->error;
}
/**
 * 登记信息通知接收人 
 */
$sql = "create table if not exists xxt_enroll_receiver(";
$sql .= 'id int not null auto_increment';
$sql .= ',mpid varchar(32) not null';
$sql .= ',aid varchar(40) not null';
$sql .= ',identity varchar(100) not null'; 
$sql .= ",idsrc char(2) not null default ''"; 
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: '.$mysqli->error;
}
/**
 * 活动登记评分
 * enroll_key mpid+uniqid+name 
 */
$sql = 'create table if not exists xxt_enroll_record(';
$sql .= 'id int not null auto_increment';
$sql .= ',aid varchar(40) not null';
$sql .= ',mpid varchar(32) not null';
$sql .= ',rid varchar(13) not null';
$sql .= ",openid varchar(255) not null default ''";
$sql .= ",nickname varchar(255) not null default ''";
$sql .= ",enroll_key varchar(32) not null";
$sql .= ",enroll_at int not null"; // 填写报名信息时间
$sql .= ",signin_at int not null"; // 签到时间
$sql .= ",tags text";
$sql .= ",comment text";
$sql .= ',vid varchar(32)';
$sql .= ',mid varchar(32)';
$sql .= ',score int not null default 0'; // 点赞数
$sql .= ",remark_num int not null default 0"; // 评论数
$sql .= ',primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8';
if (!$mysqli->query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: '.$mysqli->error;
}
/**
 * 活动登记评分 
 */
$sql = "create table if not exists xxt_enroll_record_score(";
$sql .= 'id int not null auto_increment';
$sql .= ",enroll_key varchar(32) not null";
$sql .= ",openid varchar(255)";
$sql .= ',create_at int not null';
$sql .= ',score int not null default 0';
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: '.$mysqli->error;
}
/**
 * 活动登记评
 */
$sql = "create table if not exists xxt_enroll_record_remark(";
$sql .= 'id int not null auto_increment';
$sql .= ",enroll_key varchar(32) not null";
$sql .= ",openid varchar(255)";
$sql .= ',create_at int';
$sql .= ',remark text';
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: '.$mysqli->error;
}
/**
 * 活动报名自定义信息
 */
$sql = 'create table if not exists xxt_enroll_record_data(';
$sql .= 'aid varchar(40) not null';
$sql .= ",enroll_key varchar(32) not null";
$sql .= ',name varchar(40) not null';
$sql .= ',value text';
$sql .= ',primary key(aid,enroll_key,name)) ENGINE=MyISAM DEFAULT CHARSET=utf8';
if (!$mysqli->query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: '.$mysqli->error;
}
/*
 * 通用活动抽奖轮次
 */
$sql = 'create table if not exists xxt_enroll_lottery_round(';
$sql .= 'aid varchar(40) not null';
$sql .= ",round_id varchar(32) not null";
$sql .= ",create_at int not null";
$sql .= ",title varchar(40) not null";
$sql .= ",autoplay char(1) not null default 'N'"; // 自动抽奖直到达到抽奖次数
$sql .= ",times int not null"; // 抽奖次数
$sql .= ",targets text";
$sql .= ',primary key(aid,round_id)) ENGINE=MyISAM DEFAULT CHARSET=utf8';
if (!$mysqli->query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: '.$mysqli->error;
}
/*
 * 通用活动抽奖结果
 */
$sql = 'create table if not exists xxt_enroll_lottery(';
$sql .= 'aid varchar(40) not null';
$sql .= ",round_id varchar(32) not null";
$sql .= ",enroll_key varchar(32) not null";
$sql .= ",draw_at int not null";
$sql .= ",openid varchar(255) not null default ''";
$sql .= ',primary key(aid,round_id,enroll_key)) ENGINE=MyISAM DEFAULT CHARSET=utf8';
if (!$mysqli->query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: '.$mysqli->error;
}

echo 'finish enroll.'.PHP_EOL;
