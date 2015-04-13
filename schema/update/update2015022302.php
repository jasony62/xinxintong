<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "ALTER TABLE  `xxt_shareaction_log` CHANGE  `sid`  `shareid` VARCHAR(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL";
$sqls[] = "ALTER TABLE `xxt_shareaction_log` DROP `msrc`";
$sqls[] = "ALTER TABLE `xxt_shareaction_log` DROP `mopenid`";
$sqls[] = "ALTER TABLE  `xxt_shareaction_log` CHANGE  `mshareid`  `matter_shareby` VARCHAR(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL";
$sqls[] = "ALTER TABLE  `xxt_matter_read_log` CHANGE  `shareid`  `matter_shareby` VARCHAR(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL";
$sqls[] = "ALTER TABLE `xxt_matter_read_log` DROP `src`";
$sqls[] = "ALTER TABLE `xxt_matter_read_log` DROP `openid`";

foreach ($sqls as $sql) {
    if (!mysql_query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.mysql_error();
    }
}
echo "end update ".__FILE__.PHP_EOL;
