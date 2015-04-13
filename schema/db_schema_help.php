<?php
require_once '../db.php';
/**
 * 文档 
 */ 
$sql = "create table if not exists tms_helpdoc(";
$sql .= 'id int not null auto_increment';
$sql .= ",creater varchar(40) not null default ''";
$sql .= ',create_at int not null';
$sql .= ',modify_at int not null';
$sql .= ",state char(1) not null default 'E'"; //E:editing,P:published
$sql .= ',title varchar(70) not null';
$sql .= ',content text';
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!mysql_query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: '.mysql_error();
}
echo 'finish help'.PHP_EOL;
