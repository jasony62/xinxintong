<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "ALTER TABLE  `xxt_activity` ADD  `intro_js` TEXT NOT NULL AFTER  `intro_ele`";
$sqls[] = "ALTER TABLE  `xxt_activity` ADD  `state_js` TEXT NOT NULL AFTER  `state_ele`";

foreach ($sqls as $sql) {
    if (!mysql_query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.mysql_error();
    }
}
echo "end update ".__FILE__.PHP_EOL;
