<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "ALTER TABLE  `xxt_mpsend_log` CHANGE  `matter_id`  `matter_id` VARCHAR( 40 ) NOT NULL ";
$sqls[] = "ALTER TABLE  `xxt_mpsend_log` CHANGE  `matter_type`  `matter_type` VARCHAR( 20 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL";
$sqls[] = "ALTER TABLE  `xxt_matter_read_log` CHANGE  `matter_id`  `matter_id` VARCHAR( 40 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL";
$sqls[] = "ALTER TABLE  `xxt_matter_read_log` CHANGE  `matter_type`  `matter_type` VARCHAR( 20 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL";
$sqls[] = "ALTER TABLE  `xxt_shareaction_log` CHANGE  `matter_id`  `matter_id` VARCHAR( 40 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL";
$sqls[] = "ALTER TABLE  `xxt_shareaction_log` CHANGE  `matter_type`  `matter_type` VARCHAR( 20 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL";
$sqls[] = "ALTER TABLE  `xxt_matter_action_log` CHANGE  `matter_id`  `matter_id` VARCHAR( 40 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL";
$sqls[] = "ALTER TABLE  `xxt_matter_action_log` CHANGE  `matter_type`  `matter_type` VARCHAR( 20 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL";
$sqls[] = "ALTER TABLE  `xxt_news` CHANGE  `empty_reply_type`  `empty_reply_type` VARCHAR( 20 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL";
$sqls[] = "ALTER TABLE  `xxt_news` CHANGE  `empty_reply_id`  `empty_reply_id` VARCHAR( 40 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL";
$sqls[] = "ALTER TABLE  `xxt_news_matter` CHANGE  `matter_id`  `matter_id` VARCHAR( 40 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL";
$sqls[] = "ALTER TABLE  `xxt_news_matter` CHANGE  `matter_type`  `matter_type` VARCHAR( 20 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL";
$sqls[] = "ALTER TABLE  `xxt_channel` CHANGE  `top_type`  `top_type` VARCHAR( 20 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL";
$sqls[] = "ALTER TABLE  `xxt_channel` CHANGE  `top_id`  `top_id` VARCHAR( 40 ) NULL";
$sqls[] = "ALTER TABLE  `xxt_channel` CHANGE  `bottom_type`  `bottom_type` VARCHAR( 20 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL";
$sqls[] = "ALTER TABLE  `xxt_channel` CHANGE  `bottom_id`  `bottom_id` VARCHAR( 40 ) NULL";
$sqls[] = "ALTER TABLE  `xxt_channel_matter` CHANGE  `matter_id`  `matter_id` VARCHAR( 40 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL";
$sqls[] = "ALTER TABLE  `xxt_channel_matter` CHANGE  `matter_type`  `matter_type` VARCHAR( 20 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL";
$sqls[] = "ALTER TABLE  `xxt_matter_acl` CHANGE  `matter_type`  `matter_type` CHAR( 20 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL";
$sqls[] = "ALTER TABLE  `xxt_matter_acl` CHANGE  `matter_id`  `matter_id` VARCHAR( 40 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL";
$sqls[] = "ALTER TABLE  `xxt_text_call_reply` CHANGE  `matter_type`  `matter_type` VARCHAR( 20 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL";
$sqls[] = "ALTER TABLE  `xxt_text_call_reply` CHANGE  `matter_id`  `matter_id` VARCHAR( 40 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL";
$sqls[] = "ALTER TABLE  `xxt_menu_reply` CHANGE  `matter_type`  `matter_type` VARCHAR( 20 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL";
$sqls[] = "ALTER TABLE  `xxt_menu_reply` CHANGE  `matter_id`  `matter_id` VARCHAR( 40 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL";
$sqls[] = "ALTER TABLE  `xxt_qrcode_call_reply` CHANGE  `matter_type`  `matter_type` VARCHAR( 20 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL";
$sqls[] = "ALTER TABLE  `xxt_qrcode_call_reply` CHANGE  `matter_id`  `matter_id` VARCHAR( 40 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL";
$sqls[] = "ALTER TABLE  `xxt_other_call_reply` CHANGE  `matter_type`  `matter_type` VARCHAR( 20 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL";
$sqls[] = "ALTER TABLE  `xxt_other_call_reply` CHANGE  `matter_id`  `matter_id` VARCHAR( 40 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL";

foreach ($sqls as $sql) {
    if (!$mysqli->query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.$mysqli->error;
    }
}
echo "end update ".__FILE__.PHP_EOL;
