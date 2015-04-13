<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "ALTER TABLE `xxt_member` ADD `sync_at` INT NOT NULL DEFAULT '0' AFTER `create_at`";
$sqls[] = "ALTER TABLE `xxt_member_department` ADD `sync_at` INT NOT NULL DEFAULT '0' AFTER `seq`";
$sqls[] = "ALTER TABLE `xxt_member_tag` ADD `sync_at` INT NOT NULL DEFAULT '0' AFTER `id`";

foreach ($sqls as $sql) {
    if (!mysql_query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.mysql_error();
    }
}
echo "end update ".__FILE__.PHP_EOL;
