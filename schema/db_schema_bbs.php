<?php
require_once '../db.php';
/**
 * bbs setting
 */
$sql = 'create table if not exists xxt_bbs';
$sql .= '(mpid varchar(32) not null';
$sql .= ',list_css text';
$sql .= ',list_ele text';
$sql .= ',list_js text';
$sql .= ',subject_css text';
$sql .= ',subject_ele text';
$sql .= ',subject_js text';
$sql .= ',publish_css text';
$sql .= ',publish_ele text';
$sql .= ',publish_js text';
$sql .= ',primary key(mpid)) ENGINE=MyISAM DEFAULT CHARSET=utf8';
if (!mysql_query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: ' . mysql_error();
}
/*
 * bbs subject
 */
$sql = 'create table if not exists xxt_bbs_subject';
$sql .= '(sid int not null auto_increment';
$sql .= ',mpid varchar(32) not null';
$sql .= ',subject varchar(255) not null';
$sql .= ',content text';
$sql .= ',creater varchar(32) not null';
$sql .= ",publish_at int not null";
$sql .= ",reply_at int not null default 0";
$sql .= ',primary key(sid)) ENGINE=MyISAM DEFAULT CHARSET=utf8';
if (!mysql_query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: ' . mysql_error();
}
/*
 * bbs reply
 */
$sql = 'create table if not exists xxt_bbs_reply';
$sql .= '(rid int not null auto_increment';
$sql .= ',sid int not null';
$sql .= ',mpid varchar(32) not null';
$sql .= ',content text';
$sql .= ',creater varchar(32) not null';
$sql .= ",reply_at int not null";
$sql .= ',primary key(rid)) ENGINE=MyISAM DEFAULT CHARSET=utf8';
if (!mysql_query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: ' . mysql_error();
}
echo 'finish bbs.'.PHP_EOL;
