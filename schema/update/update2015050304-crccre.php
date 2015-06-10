<?php
require_once '../../db.php';

$sql = array();
$sql[] = 'drop table if exists xxt_inner';
/**
 * 执行操作
 */
foreach ($sql as $s) {
    if (!$mysqli->query($s)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.$mysqli->error;
    }
}
/*
 * 活动
 */
$sql = "create table if not exists xxt_inner(";
$sql .= 'id int not null';
$sql .= ',title varchar(70) not null';
$sql .= ',name varchar(30) not null';
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: '.$mysqli->error;
}

$sql = array();
$sql[] = "INSERT INTO xxt_inner(id,title,name) VALUES(1,'通讯录','addressbook')";
$sql[] = "INSERT INTO xxt_inner(id,title,name) VALUES(3, '翻译', 'translate')";
$sql[] = "INSERT INTO xxt_inner(id,title,name) VALUES(4, '按关键字搜索文章', 'fullsearch')";
// 执行
foreach ($sql as $s) {
    if (!$mysqli->query($s)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo "database error: ".$mysqli->error;
    }
}
echo 'finished.';
