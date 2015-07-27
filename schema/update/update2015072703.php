<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "alter table xxt_enroll_record add column nickname varchar(255) not null default '' after openid";
$sqls[] = "update xxt_enroll_record r,xxt_fans f set r.nickname=f.nickname where r.mpid=f.mpid and r.openid=f.openid";

foreach ($sqls as $sql) {
    if (!$mysqli->query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.$mysqli->error;
    }
}
echo "end update ".__FILE__.PHP_EOL;
