<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "alter table xxt_enroll add column multi_rounds char(1) not null default 'N' after open_lastroll";
$sqls[] = "update xxt_enroll e set multi_rounds='Y' where exists (select 1 from xxt_enroll_round r where r.aid=e.id)";

foreach ($sqls as $sql) {
    if (!$mysqli->query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.$mysqli->error;
    }
}
echo "end update ".__FILE__.PHP_EOL;
