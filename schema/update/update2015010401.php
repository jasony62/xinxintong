<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "alter table `xxt_ab_person` drop `out_of_date`";

foreach ($sqls as $sql) {
    if (!mysql_query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.mysql_error();
    }
}
echo "end update ".__FILE__.PHP_EOL;
