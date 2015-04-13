<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "ALTER TABLE `xxt_wall` ADD `push_others` CHAR(1) NOT NULL DEFAULT 'Y' AFTER `skip_approve`";
$sqls[] = "ALTER TABLE `xxt_wall` ADD `access_control` CHAR( 1 ) NOT NULL DEFAULT 'N' AFTER `title`";
$sqls[] = "ALTER TABLE `xxt_wall` ADD `authapis` TEXT NOT NULL AFTER `access_control`";

foreach ($sqls as $sql) {
    if (!mysql_query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.mysql_error();
    }
}
echo "end update ".__FILE__.PHP_EOL;
