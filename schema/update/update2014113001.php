<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "ALTER TABLE `xxt_mpaccount` CHANGE `yx_appid` `yx_appid` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL";
$sqls[] = "ALTER TABLE `xxt_mpaccount` CHANGE `yx_appsecret` `yx_appsecret` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL";
$sqls[] = "ALTER TABLE `xxt_mpaccount` CHANGE `wx_appid` `wx_appid` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL";
$sqls[] = "ALTER TABLE `xxt_mpaccount` CHANGE `wx_appsecret` `wx_appsecret` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL";
$sqls[] = "ALTER TABLE `xxt_activity` ADD `wxyx_only` CHAR( 1 ) NOT NULL DEFAULT  'N' AFTER  `end_at`";
$sqls[] = "ALTER TABLE `xxt_activity_enroll` DROP `name`";
$sqls[] = "ALTER TABLE `xxt_activity_enroll` DROP `mobile`";
$sqls[] = "ALTER TABLE  `xxt_activity_enroll` DROP  `email`";
    

foreach ($sqls as $sql) {
    if (!mysql_query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.mysql_error();
    }
}

echo 'end 2014113001'.PHP_EOL;
