<?php
require_once '../../db.php';
$sqls = array();
$sqls[] = "ALTER TABLE `xxt_activity` ADD `tags` TEXT NOT NULL";
$sqls[] = "ALTER TABLE `xxt_activity_lottery_round` ADD `targets` TEXT NOT NULL";
$sqls[] = "ALTER TABLE `xxt_activity_lottery` ADD `enroll_key` VARCHAR( 32 ) NOT NULL DEFAULT '' AFTER `round_id`";
$sqls[] = "ALTER TABLE `xxt_activity_lottery` DROP PRIMARY KEY , ADD PRIMARY KEY (`aid`,`round_id`,`enroll_key`)";

foreach ($sqls as $sql) {
    if (!mysql_query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.mysql_error();
    }
}
echo "end update ".__FILE__.PHP_EOL;
