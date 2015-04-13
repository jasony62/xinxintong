<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "ALTER TABLE `xxt_lottery_award` ADD `taskid` varchar(32) NOT NULL DEFAULT '' AFTER `type`";

foreach ($sqls as $sql) {
    if (!mysql_query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.mysql_error();
    }
}

echo 'end update 2014121401'.PHP_EOL;
