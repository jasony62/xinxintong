<?php
require_once '../db.php';
/*
 * tags
 */
$sql = 'create table if not exists xxt_tag';
$sql .= '(id int not null auto_increment';
$sql .= ',mpid varchar(32) not null';
$sql .= ',title varchar(255) not null';
$sql .= ',primary key(id)';
$sql .= ',UNIQUE KEY `tag` (mpid,title)) ENGINE=MyISAM DEFAULT CHARSET=utf8';
if (!mysql_query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: '.mysql_error();
}
/*
 * relation of tag and article.
 */
$sql = 'create table if not exists xxt_article_tag';
$sql .= '(mpid varchar(32) not null';
$sql .= ',res_id int not null';
$sql .= ',tag_id int not null';
$sql .= ',primary key(mpid,res_id,tag_id)) ENGINE=MyISAM DEFAULT CHARSET=utf8';
if (!mysql_query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: '.mysql_error();
}

echo 'finish tag.'.PHP_EOL;
