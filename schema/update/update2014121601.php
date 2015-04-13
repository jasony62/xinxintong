<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "ALTER TABLE `xxt_wall` ADD `body_css` TEXT NOT NULL DEFAULT ''";

foreach ($sqls as $sql) {
    if (!mysql_query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.mysql_error();
    }
}
echo 'end update 2014121601'.PHP_EOL;
