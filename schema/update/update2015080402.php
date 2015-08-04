<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "ALTER TABLE xxt_mpsetting add column follow_pic text after follow_css";
$sqls[] = "ALTER TABLE xxt_mpsetting add column header_page_id int not null default 0 after heading_pic";
$sqls[] = "ALTER TABLE xxt_mpsetting add column footer_page_id int not null default 0 after header_page_id";
$sqls[] = "ALTER TABLE xxt_mpsetting add column follow_page_id int not null default 0 after footer_page_id";

foreach ($sqls as $sql) {
    if (!$mysqli->query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.$mysqli->error;
    }
}
echo "end update ".__FILE__.PHP_EOL;
