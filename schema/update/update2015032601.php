<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "ALTER TABLE `xxt_member` ADD `password` CHAR(64) NOT NULL AFTER `email`";
$sqls[] = "ALTER TABLE `xxt_member` ADD `password_salt` CHAR( 32 ) NOT NULL AFTER `password` ";
$sqls[] = "ALTER TABLE `xxt_member` ADD `mobile_verified` CHAR(1) NOT NULL DEFAULT 'Y' AFTER `weixinid`";
$sqls[] = "ALTER TABLE `xxt_member_authapi` ADD `attr_password` CHAR(6) NOT NULL DEFAULT '110000' AFTER `attr_name`";

foreach ($sqls as $sql) {
    if (!mysql_query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.mysql_error();
    }
}
echo "end update ".__FILE__.PHP_EOL;
