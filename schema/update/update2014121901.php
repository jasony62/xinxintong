<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "ALTER TABLE `xxt_wall` CHANGE `reply` `join_reply` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL";
$sqls[] = "ALTER TABLE `xxt_wall` ADD `quit_reply` TEXT NOT NULL AFTER `join_reply`";

foreach ($sqls as $sql) {
    if (!mysql_query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.mysql_error();
    }
}
echo 'end update 2014121901'.PHP_EOL;
