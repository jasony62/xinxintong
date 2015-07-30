<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "alter table xxt_article add column share_friend_num int not null default 0 after remark_num";
$sqls[] = "alter table xxt_article add column share_timeline_num int not null default 0 after share_friend_num";
$sqls[] = "alter table xxt_enroll add column read_num int not null default 0";
$sqls[] = "alter table xxt_enroll add column share_friend_num int not null default 0";
$sqls[] = "alter table xxt_enroll add column share_timeline_num int not null default 0";

foreach ($sqls as $sql) {
    if (!$mysqli->query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.$mysqli->error;
    }
}
echo "end update ".__FILE__.PHP_EOL;
