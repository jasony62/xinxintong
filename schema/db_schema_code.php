<?php
require_once '../db.php';
/*
 * pages
 */
$sql = 'create table if not exists xxt_code_page(';
$sql .= 'id int not null auto_increment';
$sql .= ',creater varchar(40) not null';
$sql .= ',create_at int not null';
$sql .= ',modify_at int not null';
$sql .= ',title varchar(255) not null';
$sql .= ',summary varchar(240) not null';
$sql .= ',html text';
$sql .= ',css text';
$sql .= ',js text';
$sql .= ',primary key(id)';
$sql .= ') ENGINE=MyISAM DEFAULT CHARSET=utf8';
if (!mysql_query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: '.mysql_error();
}

echo 'finish code.'.PHP_EOL;
