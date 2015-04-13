<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "ALTER TABLE `xxt_member` ADD `weixinid` VARCHAR( 16 ) NOT NULL DEFAULT '' AFTER `email`";
$sqls[] = "ALTER TABLE `xxt_member_tag` ADD `extattr` TEXT NOT NULL";

foreach ($sqls as $sql) {
    if (!mysql_query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.mysql_error();
    }
}
echo 'end update 2014122301'.PHP_EOL;
