<?php
require_once '../db.php';
/**
 * 投稿应用 
 */ 
$sql = 'create table if not exists xxt_task (';
$sql .= 'code char(4) not null';
$sql .= ',mpid varchar(32) not null';
$sql .= ',fid varchar(255) not null';
$sql .= ',url text not null';
$sql .= ',create_at int not null';
$sql .= ",primary key(code)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!$mysqli->query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error(xxt_contribute): '.$mysqli->error;
}

echo 'finish xxt_task.'.PHP_EOL;
