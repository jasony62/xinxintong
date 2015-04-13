<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "ALTER TABLE  `xxt_mpreceive_log` CHANGE  `from_user`  `openid` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL";
$sqls[] = "alter table xxt_mpreceive_log drop `src`";
$sqls[] = "ALTER TABLE `xxt_mpsend_log` DROP `src`";

foreach ($sqls as $sql) {
    if (!mysql_query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.mysql_error();
    }
}
echo "end update ".__FILE__.PHP_EOL;
