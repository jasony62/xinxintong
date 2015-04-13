<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "ALTER TABLE  `xxt_fans` ADD  `sync_at` INT NOT NULL AFTER  `unsubscribe_at`";
$sqls[] = "update xxt_fans set sync_at=subscribe_at";

foreach ($sqls as $sql) {
    if (!mysql_query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.mysql_error();
    }
}
echo "end update ".__FILE__.PHP_EOL;
