<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "ALTER TABLE `xxt_activity` ADD `open_lastroll` CHAR(1) NOT NULL DEFAULT 'Y' AFTER `skip_enroll`";

foreach ($sqls as $sql) {
    if (!mysql_query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.mysql_error();
    }
}
echo "end update ".__FILE__.PHP_EOL;
