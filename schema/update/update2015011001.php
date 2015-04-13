<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "ALTER TABLE `xxt_call_acl` ADD `label` VARCHAR(255) NOT NULL DEFAULT ''";
$sqls[] = "ALTER TABLE `xxt_matter_acl` ADD `label` VARCHAR(255) NOT NULL DEFAULT ''";
$sqls[] = "ALTER TABLE `xxt_wall_acl` ADD `label` VARCHAR(255) NOT NULL DEFAULT ''";
$sqls[] = "ALTER TABLE `xxt_wall_acl` CHANGE `wid` `act_id` VARCHAR(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL";
$sqls[] = "ALTER TABLE `xxt_wall_acl` ADD `act_type` CHAR( 1 ) NOT NULL DEFAULT '' AFTER `mpid`";
$sqls[] = "update xxt_wall_acl set act_type='W'";
$sqls[] = "RENAME TABLE `xxt_wall_acl` TO `xxt_act_acl`";
$sqls[] = "ALTER TABLE `xxt_activity` CHANGE `member_only` `access_control` CHAR(1) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'N'";
$sqls[] = "ALTER TABLE `xxt_lottery` CHANGE `member_only` `access_control` CHAR(1) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'N'";
$sqls[] = "ALTER TABLE  `xxt_lottery` CHANGE  `title`  `title` VARCHAR(70) NOT NULL";
$sqls[] = "ALTER TABLE `xxt_wall` CHANGE `title` `title` VARCHAR(70) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL";
$sqls[] = "ALTER TABLE `xxt_activity` CHANGE `title` `title` VARCHAR(70) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL";
$sqls[] = "ALTER TABLE `xxt_wall` ADD `pic` TEXT NOT NULL AFTER `title`";
$sqls[] = "ALTER TABLE `xxt_wall` ADD `summary` VARCHAR( 240 ) NOT NULL AFTER `pic`";
$sqls[] = "ALTER TABLE  `xxt_news` ADD  `filter_by_matter_acl` CHAR( 1 ) NOT NULL DEFAULT  'Y' AFTER  `authapis`";

foreach ($sqls as $sql) {
    if (!mysql_query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.mysql_error();
    }
}
echo "end update ".__FILE__.PHP_EOL;
