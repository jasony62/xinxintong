<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "ALTER TABLE `xxt_wall` ADD `entry_ele` TEXT NOT NULL DEFAULT '' AFTER `push_others`";
$sqls[] = "ALTER TABLE `xxt_wall` ADD `entry_css` TEXT NOT NULL DEFAULT '' AFTER `entry_ele`";
$sqls[] = "ALTER TABLE `xxt_wall` ADD `user_url` TEXT NOT NULL DEFAULT '' AFTER `authapis`";

foreach ($sqls as $sql) {
    if (!mysql_query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.mysql_error();
    }
}
echo "end update ".__FILE__.PHP_EOL;
