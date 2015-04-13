<?php
require_once '../db.php';
/**
 * 投稿人工具箱
 */
$sql = 'create table if not exists xxt_writer_box(';
$sql .= 'code char(6) not null';
$sql .= ',mpid varchar(32) not null';
$sql .= ',src char(2) not null';
$sql .= ',openid varchar(255) not null';
$sql .= ',expire_at int not null';
$sql .= ',auth_mode tinyint not null default 0'; //0:不验证，1:验证码，2:二维码
$sql .= ',auth_code char(6) not null';
$sql .= ',qr_passed tinyint not null default 0';
$sql .= ",primary key(mpid,src,openid)";
$sql .= ",unique index(code)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if (!mysql_query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: '.mysql_error();
}
//
echo 'finish writer.'.PHP_EOL;
