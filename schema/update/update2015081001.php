<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "alter table xxt_article add download_num int not null default '0' after has_attachment";
$sql = "create table if not exists xxt_article_download_log(";
$sql .= 'id int not null auto_increment';
$sql .= ',vid varchar(32) not null';
$sql .= ',openid varchar(255) not null';
$sql .= ',nickname varchar(255) not null';
$sql .= ',download_at int not null';
$sql .= ',mpid varchar(32) not null';
$sql .= ',article_id int not null';
$sql .= ',attachment_id int not null';
$sql .= ",user_agent text";
$sql .= ",client_ip varchar(40) not null default ''";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;

foreach ($sqls as $sql) {
    if (!$mysqli->query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.$mysqli->error;
    }
}
echo "end update ".__FILE__.PHP_EOL;
