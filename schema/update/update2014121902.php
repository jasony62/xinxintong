<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "ALTER TABLE `xxt_lottery` ADD `nostart_alert` TEXT NOT NULL AFTER `nochance_alert`";
$sqls[] = "ALTER TABLE `xxt_lottery` ADD `hasend_alert` TEXT NOT NULL AFTER `nostart_alert`";

foreach ($sqls as $sql) {
    if (!mysql_query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.mysql_error();
    }
}
echo 'end update 2014121902'.PHP_EOL;
