<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "ALTER TABLE  `xxt_member_authapi` CHANGE  `field_mobile`  `attr_mobile` CHAR( 6 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT  '001000'";
$sqls[] = "ALTER TABLE  `xxt_member_authapi` CHANGE  `field_email`  `attr_email` CHAR( 6 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT  '001000'";
$sqls[] = "ALTER TABLE  `xxt_member_authapi` CHANGE  `field_name`  `attr_name` CHAR( 6 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT  '000000'";
$sqls[] = "ALTER TABLE `xxt_member_authapi` DROP `field_nickname`";
$sqls[] = "ALTER TABLE `xxt_member_authapi` DROP `field_birthday`";
$sqls[] = "ALTER TABLE `xxt_member` DROP `nickname`";
$sqls[] = "ALTER TABLE `xxt_member` DROP `birthday`";
$sqls[] = "ALTER TABLE `xxt_member` DROP `position`";

foreach ($sqls as $sql) {
    if (!mysql_query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.mysql_error();
    }
}
echo "end update ".__FILE__.PHP_EOL;
