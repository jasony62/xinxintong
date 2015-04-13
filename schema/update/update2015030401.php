<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "ALTER TABLE  `xxt_wall_log` ADD  `mpid` VARCHAR( 32 ) NOT NULL AFTER  `id`";
$sqls[] = "update xxt_wall_log l,xxt_wall w set l.mpid=w.mpid where l.wid=w.wid";

foreach ($sqls as $sql) {
    if (!mysql_query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.mysql_error();
    }
}
echo "end update ".__FILE__.PHP_EOL;
