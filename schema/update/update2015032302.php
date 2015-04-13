<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "ALTER TABLE `xxt_activity` ADD `can_signin` CHAR(1) NOT NULL DEFAULT 'N'";
$sqls[] = "ALTER TABLE `xxt_activity` ADD `can_lottery` CHAR(1) NOT NULL DEFAULT 'N'";

foreach ($sqls as $sql) {
    if (!mysql_query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.mysql_error();
    }
}
echo "end update ".__FILE__.PHP_EOL;
