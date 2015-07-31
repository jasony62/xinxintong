<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "ALTER TABLE xxt_log_matter_read CHANGE `ooid` `openid` VARCHAR(40) NOT NULL default ''";
$sqls[] = "ALTER TABLE xxt_log_matter_read add column nickname VARCHAR(255) NOT NULL default '' after openid";
$sqls[] = "ALTER TABLE xxt_log_matter_share CHANGE `ooid` `openid` VARCHAR(40) NOT NULL default ''";
$sqls[] = "ALTER TABLE xxt_log_matter_share add column nickname VARCHAR(255) NOT NULL default '' after openid";
$sqls[] = "ALTER TABLE xxt_log_user_action add column nickname VARCHAR(255) NOT NULL default '' after openid";
$sqls[] = "update xxt_log_matter_read m,xxt_fans f set m.nickname=f.nickname where m.openid=f.openid and m.mpid=f.mpid";
$sqls[] = "update xxt_log_matter_share m,xxt_fans f set m.nickname=f.nickname where m.openid=f.openid and m.mpid=f.mpid";
$sqls[] = "update xxt_log_user_action u,xxt_fans f set u.nickname=f.nickname where u.openid=f.openid and u.mpid=f.mpid";

foreach ($sqls as $sql) {
    if (!$mysqli->query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.$mysqli->error;
    }
}
echo "end update ".__FILE__.PHP_EOL;
