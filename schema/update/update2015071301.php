<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "alter table xxt_enroll add column before_start_page varchar(20) not null default '' after start_at";
$sqls[] = "alter table xxt_enroll add column after_end_page varchar(20) not null default '' after end_at";

foreach ($sqls as $sql) {
    if (!$mysqli->query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.$mysqli->error;
    }
}
echo "end update ".__FILE__.PHP_EOL;
