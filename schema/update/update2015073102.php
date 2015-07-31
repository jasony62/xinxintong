<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "ALTER TABLE xxt_article_remark add `openid` VARCHAR(255) NOT NULL default '' after fid";
$sqls[] = "ALTER TABLE xxt_article_remark add `nickname` VARCHAR(255) NOT NULL default '' after openid";
$sqls[] = "update xxt_article_remark a,xxt_fans f set a.openid=f.openid,a.nickname=f.nickname where a.fid=f.fid";

foreach ($sqls as $sql) {
    if (!$mysqli->query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.$mysqli->error;
    }
}
echo "end update ".__FILE__.PHP_EOL;
