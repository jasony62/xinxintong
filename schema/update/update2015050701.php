<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "ALTER TABLE  `xxt_contribute` ADD  `can_taskcode` CHAR( 1 ) NULL DEFAULT  'N' AFTER  `shift2pc`";
$sqls[] = "ALTER TABLE  `xxt_enroll` ADD  `can_taskcode` CHAR( 1 ) NULL DEFAULT  'N' AFTER  `shift2pc`";

foreach ($sqls as $sql) {
    if (!$mysqli->query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.$mysqli->error;
    }
}
echo "end update ".__FILE__.PHP_EOL;
