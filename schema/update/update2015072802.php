<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "alter table xxt_member add column openid varchar(255) after mpid";
$sqls[] = "alter table xxt_member add column nickname varchar(255) after openid";
$sqls[] = "update xxt_member m,xxt_fans f set m.openid=f.openid,m.nickname=f.nickname where m.mpid=f.mpid and m.fid=f.fid";

foreach ($sqls as $sql) {
    if (!$mysqli->query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.$mysqli->error;
    }
}
echo "end update ".__FILE__.PHP_EOL;
