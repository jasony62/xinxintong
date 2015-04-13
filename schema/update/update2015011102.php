<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "ALTER TABLE  `xxt_news` ADD  `empty_reply_type` VARCHAR( 14 ) NOT NULL";
$sqls[] = "ALTER TABLE  `xxt_news` ADD  `empty_reply_id` VARCHAR( 128 ) NOT NULL";

foreach ($sqls as $sql) {
    if (!mysql_query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.mysql_error();
    }
}
echo "end update ".__FILE__.PHP_EOL;
