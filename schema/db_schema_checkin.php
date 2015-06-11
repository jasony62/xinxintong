<?php
require_once '../db.php';
/**
 * 签到设置表，设置签到规则
 */
$sql = 'create table if not exists xxt_checkin';
$sql .= '(mpid varchar(32) not null';
$sql .= ',extra_css text';
$sql .= ',extra_ele text';
$sql .= ',extra_js text';
$sql .= ',primary key(mpid)) ENGINE=MyISAM DEFAULT CHARSET=utf8';
if (!$mysqli->query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: ' . $mysqli->error;
}
/*
 * member's checkin
 * 签到记录表，记录用户的每一次签到行为
 * 每一次签到行为上记录，已经累计签到的次数和是否为最后一次的标识
 * 
 * 记录每一次签到行为
 * 最有一次签到行为的last=1其他为0
 * 每一条签到记录都记录累计的有效签到次数
 */
$sql = 'create table if not exists xxt_checkin_log';
$sql .= '(id int not null auto_increment';
$sql .= ',mid varchar(32) not null';
$sql .= ',mpid varchar(32) not null';
$sql .= ',checkin_at int not null';
$sql .= ",times_accumulated int not null default 1";
$sql .= ",last int not null default 1";
$sql .= ',primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8';
if (!$mysqli->query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: ' . $mysqli->error;
}
echo 'finish checkin.'.PHP_EOL;
