<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "ALTER TABLE `xxt_mpaccount` ADD `qy_corpid` VARCHAR(255) NOT NULL DEFAULT '' AFTER `wx_token_expire_at`";
$sqls[] = "ALTER TABLE `xxt_mpaccount` ADD `qy_secret` VARCHAR(255) NOT NULL DEFAULT '' AFTER `qy_corpid`";
$sqls[] = "ALTER TABLE `xxt_mpaccount` ADD `qy_encodingaeskey` VARCHAR(43) NOT NULL DEFAULT '' AFTER `qy_secret`";
$sqls[] = "ALTER TABLE `xxt_mpaccount` ADD `qy_agentid` INT NOT NULL DEFAULT '0' AFTER `qy_encodingaeskey`";
$sqls[] = "ALTER TABLE `xxt_mpaccount` ADD `qy_joined` CHAR( 1 ) NOT NULL DEFAULT 'N' AFTER `qy_agentid`";
$sqls[] = "ALTER TABLE `xxt_mpaccount` ADD `qy_token` TEXT NOT NULL DEFAULT '' AFTER `qx_joined`";
$sqls[] = "ALTER TABLE `xxt_mpaccount` ADD `qy_token_expire_at` INT NOT NULL AFTER `qy_token`";  

foreach ($sqls as $sql) {
    if (!mysql_query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.mysql_error();
    }
}
echo 'end update 2014122201'.PHP_EOL;
