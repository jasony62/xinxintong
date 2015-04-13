<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "DROP TABLE xxt_writer_box";
$sqls[] = "DELETE FROM `xxt`.`xxt_inner` WHERE `xxt_inner`.`id` = 6";
$sqls[] = "DELETE FROM `xxt`.`xxt_inner` WHERE `xxt_inner`.`id` = 9";

foreach ($sqls as $sql) {
    if (!mysql_query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.mysql_error();
    }
}
echo "end update ".__FILE__.PHP_EOL;
