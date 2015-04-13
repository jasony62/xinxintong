<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "ALTER TABLE  `xxt_mpaccount` ADD  `mpsrc` CHAR( 2 ) NOT NULL AFTER  `mpid`";
$sqls[] = "update xxt_mpaccount set mpsrc = 'yx' where yx_joined = 'Y'";
$sqls[] = "update xxt_mpaccount set mpsrc = 'wx' where wx_joined = 'Y'";
$sqls[] = "update xxt_mpaccount set mpsrc = 'qy' where qy_joined = 'Y'";
$sqls[] = "drop table xxt_mpgroup";
$sqls[] = "alter table xxt_mpaccount drop column mpgid";

foreach ($sqls as $sql) {
    if (!mysql_query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.mysql_error();
    }
}
echo "end update ".__FILE__.PHP_EOL;
