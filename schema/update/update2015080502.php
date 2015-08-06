<?php
require_once '../../db.php';

$sqls = array();
$sql = "create table if not exists xxt_log_mpa(";
$sql .= 'id int not null auto_increment';
$sql .= ',mpid varchar(32) not null';
$sql .= ',year int not null default 0';
$sql .= ',month int not null default 0';
$sql .= ',day int not null default 0';
$sql .= ',read_inc int not null default 0'; 
$sql .= ',read_sum int not null default 0'; 
$sql .= ',sf_inc int not null default 0'; 
$sql .= ',sf_sum int not null default 0'; 
$sql .= ',st_inc int not null default 0'; 
$sql .= ',st_sum int not null default 0'; 
$sql .= ',fans_inc int not null default 0'; 
$sql .= ',fans_sum int not null default 0'; 
$sql .= ',member_inc int not null default 0'; 
$sql .= ',member_sum int not null default 0';
$sql .= ",islast char(1) not null default 'N'";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;

foreach ($sqls as $sql) {
    if (!$mysqli->query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.$mysqli->error;
    }
}
echo "end update ".__FILE__.PHP_EOL;
