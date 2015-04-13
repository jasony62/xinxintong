<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "update xxt_member_authapi set name='内置认证' where type='inner'";
$sqls[] = "drop table xxt_member_auth";
$sqls[] = "ALTER TABLE  `xxt_fans` ADD  `forbidden` CHAR( 1 ) NOT NULL DEFAULT  'N'";

foreach ($sqls as $sql) {
    if (!mysql_query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.mysql_error();
    }
}
echo "end update ".__FILE__.PHP_EOL;
