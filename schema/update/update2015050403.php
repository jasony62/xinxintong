<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "ALTER TABLE `xxt_enroll_record` DROP `src`";
$sqls[] = "ALTER TABLE `xxt_enroll_lottery` DROP `src`";
$sqls[] = "ALTER TABLE `xxt_log_matter_read` DROP `osrc`";
$sqls[] = "ALTER TABLE `xxt_log_matter_share` DROP `osrc`";
$sqls[] = "ALTER TABLE `xxt_lottery_task_log` DROP `src`";
$sqls[] = "ALTER TABLE `xxt_lottery_log` DROP `src`";
$sqls[] = "ALTER TABLE `xxt_call_qrcode` DROP `src`";
$sqls[] = "ALTER TABLE `xxt_fans` DROP `src`";
$sqls[] = "ALTER TABLE `xxt_fansgroup` DROP `src`";
$sqls[] = "ALTER TABLE `xxt_member` DROP `osrc`";
$sqls[] = "ALTER TABLE `xxt_wall_enroll` DROP `src`";
$sqls[] = "ALTER TABLE `xxt_wall_log` DROP `src`";

foreach ($sqls as $sql) {
    if (!$mysqli->query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.$mysqli->error;
    }
}
echo "end update ".__FILE__.PHP_EOL;