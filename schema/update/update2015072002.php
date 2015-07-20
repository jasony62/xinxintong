<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "alter table xxt_member add verified char(1) not null default 'N' after email_verified";
$sqls[] = "update xxt_member set verified='Y' where mobile_verified='Y' or email_verified='Y'";
$sqls[] = "ALTER TABLE `xxt_member` DROP `ooid`";

foreach ($sqls as $sql) {
    if (!$mysqli->query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.$mysqli->error;
    }
}
echo "end update ".__FILE__.PHP_EOL;
