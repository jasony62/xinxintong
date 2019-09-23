<?php
require_once '../../db.php';

$sqls = [];
//
$sqls[] = "ALTER TABLE xxt_log add request_method varchar(10) null";
$sqls[] = "ALTER TABLE xxt_log add http_accept text null";
//
foreach ($sqls as $sql) {
    if (!$mysqli->query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: ' . $mysqli->error;
    }
}

echo "end update " . __FILE__ . PHP_EOL;