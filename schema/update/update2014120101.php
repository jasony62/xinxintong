<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "ALTER TABLE  `xxt_wall` CHANGE  `active`  `active` CHAR( 1 ) NOT NULL DEFAULT  'N'";
$sqls[] = "ALTER TABLE  `xxt_wall` CHANGE  `skip_approve`  `skip_approve` CHAR( 1 ) NOT NULL DEFAULT  'N'";
$sqls[] = "ALTER TABLE  `xxt_wall_log` CHANGE  `approved`  `approved` CHAR( 1 ) NOT NULL DEFAULT  'N'";

foreach ($sqls as $sql) {
    if (!mysql_query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.mysql_error();
    }
}

echo 'end update 2014120101'.PHP_EOL;
