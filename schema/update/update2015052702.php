<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "ALTER TABLE `xxt_article` ADD `target_mps` TEXT NOT NULL DEFAULT '' AFTER `entry`";
//
foreach ($sqls as $sql) {
    if (!$mysqli->query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.$mysqli->error;
    }
}
echo "end update ".__FILE__.PHP_EOL;
