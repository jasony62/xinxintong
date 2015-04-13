<?php
require_once '../db.php';
// example
//$sql = "alter table xxt_xxx add xxxxx int not null";
//$sql = "alter table xxt_xxx change xxxxx xxxxx char(1) not null default 'N'";
//$sql = "alter table xxt_xxx drop xxxxx"
//$sqls[] = $sql;

$sqls = array();

foreach ($sqls as $sql) {
    if (!mysql_query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.mysql_error();
    }
}

echo 'end update 2014102701'.PHP_EOL;
