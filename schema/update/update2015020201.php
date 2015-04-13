<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "ALTER TABLE  `xxt_activity_lottery_round` ADD  `autoplay` CHAR( 1 ) NOT NULL DEFAULT  'N' AFTER  `title`";
$sqls[] = "ALTER TABLE  `xxt_activity_lottery_round` ADD  `times` INT NOT NULL AFTER  `autoplay`";

foreach ($sqls as $sql) {
    if (!mysql_query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.mysql_error();
    }
}
echo "end update ".__FILE__.PHP_EOL;
