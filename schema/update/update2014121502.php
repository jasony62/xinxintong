<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "ALTER TABLE  `xxt_lottery_task_log` ADD  `finished` CHAR( 1 ) NOT NULL DEFAULT  'N'";

foreach ($sqls as $sql) {
    if (!mysql_query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.mysql_error();
    }
}

echo 'end update 2014121502'.PHP_EOL;
