<?php
require_once '../db.php';
/*
 * 通用活动抽奖轮次
 */
$sql = 'create table if not exists xxt_activity_lottery_round(';
$sql .= 'aid varchar(40) not null';
$sql .= ",round_id varchar(32) not null";
$sql .= ",create_at int not null";
$sql .= ",title varchar(40) not null";
$sql .= ",autoplay char(1) not null default 'N'"; // 自动抽奖直到达到抽奖次数
$sql .= ",times int not null"; // 抽奖次数
$sql .= ",targets text";
$sql .= ',primary key(aid,round_id)) ENGINE=MyISAM DEFAULT CHARSET=utf8';
if (!mysql_query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: '.mysql_error();
}
/*
 * 通用活动抽奖结果
 */
$sql = 'create table if not exists xxt_activity_lottery(';
$sql .= 'aid varchar(40) not null';
$sql .= ",round_id varchar(32) not null";
$sql .= ",enroll_key varchar(32) not null";
$sql .= ",draw_at int not null";
$sql .= ",src char(2) not null default ''";
$sql .= ",openid varchar(255) not null default ''";
$sql .= ',primary key(aid,round_id,enroll_key)) ENGINE=MyISAM DEFAULT CHARSET=utf8';
if (!mysql_query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: '.mysql_error();
}

echo 'finish activity2.'.PHP_EOL;
