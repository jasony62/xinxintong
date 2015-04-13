<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "ALTER TABLE `xxt_member` ADD `extattr` TEXT NOT NULL DEFAULT ''";
$sqls[] = "ALTER TABLE `xxt_member_department` ADD `extattr` TEXT NOT NULL DEFAULT ''";

foreach ($sqls as $sql) {
    if (!mysql_query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.mysql_error();
    }
}

echo 'end update 2014121501'.PHP_EOL;
