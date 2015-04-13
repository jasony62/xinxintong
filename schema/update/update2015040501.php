<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "ALTER TABLE  `xxt_activity_enroll` ADD  `score` INT NOT NULL";
$sqls[] = "RENAME TABLE  `xxt`.`xxt_activity_enroll_receiver` TO  `xxt`.`xxt_activity_receiver`";
$sqls[] = "RENAME TABLE  `xxt`.`xxt_activity_cusdata` TO  `xxt`.`xxt_activity_enroll_cusdata`";

foreach ($sqls as $sql) {
    if (!mysql_query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.mysql_error();
    }
}
echo "end update ".__FILE__.PHP_EOL;
