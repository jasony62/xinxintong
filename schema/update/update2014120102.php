<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "ALTER TABLE `xxt_activity_enroll` ADD `signin_at` INT NOT NULL DEFAULT '0' AFTER `enroll_at`";
$sqls[] = "ALTER TABLE `xxt_text_call_reply` CHANGE `matter_type` `matter_type` VARCHAR(14) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL";
$sqls[] = "ALTER TABLE `xxt_qrcode_call_reply` CHANGE `matter_type` `matter_type` VARCHAR(14) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL";
$sqls[] = "ALTER TABLE `xxt_other_call_reply` CHANGE `matter_type` `matter_type` VARCHAR(14) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL";
$sqls[] = "ALTER TABLE `xxt_menu_reply` CHANGE `matter_type` `matter_type` VARCHAR(14) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL";
$sqls[] = "ALTER TABLE `xxt_activity` ADD `success_matter_type` VARCHAR(14) NOT NULL DEFAULT '' AFTER `skip_enroll`";
$sqls[] = "ALTER TABLE `xxt_activity` ADD `success_matter_id` VARCHAR(128) NOT NULL DEFAULT '' AFTER `success_matter_type`";
$sqls[] = "ALTER TABLE `xxt_activity` ADD `failure_matter_type` VARCHAR(14) NOT NULL DEFAULT '' AFTER `success_matter_id`";
$sqls[] = "ALTER TABLE `xxt_activity` ADD `failure_matter_id` VARCHAR(128) NOT NULL DEFAULT '' AFTER `failure_matter_type`";

foreach ($sqls as $sql) {
    if (!mysql_query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.mysql_error();
    }
}

echo 'end update 2014120102'.PHP_EOL;
