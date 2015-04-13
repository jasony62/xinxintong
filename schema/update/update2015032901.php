<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "ALTER TABLE `xxt_activity` DROP `intro_page_id`";
$sqls[] = "ALTER TABLE `xxt_activity` DROP `view_page_id`";
$sqls[] = "ALTER TABLE  `xxt_activity` CHANGE  `form_page_id`  `form_code_id` INT( 11 ) NOT NULL DEFAULT  '0'";
$sqls[] = "ALTER TABLE  `xxt_activity` CHANGE  `result_page_id`  `result_code_id` INT( 11 ) NOT NULL DEFAULT  '0'";
$sqls[] = "ALTER TABLE `xxt_activity` DROP `skip_intro`";
$sqls[] = "ALTER TABLE `xxt_activity` DROP `skip_intro_enrolled`";
$sqls[] = "ALTER TABLE `xxt_activity` DROP `skip_enroll`";
$sqls[] = "ALTER TABLE `xxt_activity` DROP `skip_enroll_enrolled`";
$sqls[] = "ALTER TABLE  `xxt_activity` ADD  `entry_page` VARCHAR( 20 ) NOT NULL DEFAULT  'form' AFTER  `failure_matter_id`";
$sqls[] = "ALTER TABLE  `xxt_activity` ADD  `enrolled_entry_page` VARCHAR( 20 ) NOT NULL AFTER  `entry_page`";

foreach ($sqls as $sql) {
    if (!mysql_query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.mysql_error();
    }
}
echo "end update ".__FILE__.PHP_EOL;
