<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "ALTER TABLE  `xxt_member` ADD  `position` VARCHAR( 64 ) NOT NULL AFTER  `email`";

foreach ($sqls as $sql) {
    if (!mysql_query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.mysql_error();
    }
}
echo "end update ".__FILE__.PHP_EOL;
