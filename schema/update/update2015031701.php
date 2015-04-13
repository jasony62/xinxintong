<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "ALTER TABLE  `xxt_wall_log` ADD  `data_media_id` VARCHAR( 255 ) NOT NULL AFTER  `data_type`";

foreach ($sqls as $sql) {
    if (!mysql_query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.mysql_error();
    }
}
echo "end update ".__FILE__.PHP_EOL;
