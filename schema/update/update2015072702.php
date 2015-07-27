<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "alter table xxt_enroll add column can_like_record char(1) not null default 'N' after multi_rounds";
$sqls[] = "alter table xxt_enroll add column can_remark_record char(1) not null default 'N' after can_like_record";
$sqls[] = "update xxt_enroll set can_like_record='Y'";
$sqls[] = "update xxt_enroll set can_remark_record='Y'";

foreach ($sqls as $sql) {
    if (!$mysqli->query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.$mysqli->error;
    }
}
echo "end update ".__FILE__.PHP_EOL;
