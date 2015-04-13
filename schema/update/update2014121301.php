<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "ALTER TABLE `xxt_member` ADD `depts` TEXT NOT NULL DEFAULT '' AFTER `credits`";
$sqls[] = "ALTER TABLE `xxt_call_acl` ADD `idsrc` CHAR(2) NOT NULL DEFAULT  ''";
$sqls[] = "ALTER TABLE `xxt_matter_acl` ADD `idsrc` CHAR(2) NOT NULL DEFAULT ''";

foreach ($sqls as $sql) {
    if (!mysql_query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.mysql_error();
    }
}

echo 'end update 2014121301'.PHP_EOL;
