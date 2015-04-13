<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "ALTER TABLE  `xxt_mpreceive_log` ADD  `nickname` VARCHAR( 255 ) NOT NULL AFTER  `openid`";
$sqls[] = "UPDATE xxt_mpreceive_log l, xxt_fans f SET l.nickname=f.nickname WHERE l.mpid=f.mpid AND l.openid=f.openid";

foreach ($sqls as $sql) {
    if (!mysql_query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.mysql_error();
    }
}
echo "end update ".__FILE__.PHP_EOL;
